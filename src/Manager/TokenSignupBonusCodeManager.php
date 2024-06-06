<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\TokenSignupBonusCode;
use App\Entity\TokenSignupHistory;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\TokenSignupBonusConfig;
use App\Repository\TokenSignupHistoryRepository;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class TokenSignupBonusCodeManager implements TokenSignupBonusCodeManagerInterface
{
    private const BONUS_REWARD_PRECISION = 4;
    private const CLAIM_TOKEN_SIGNUP_BONUS = 'claim_token_signup_bonus';

    private EntityManagerInterface $em;
    private balanceHandlerInterface $balanceHandler;
    private TokenManagerInterface $tokenManager;
    private TranslatorInterface $translator;
    private MoneyWrapperInterface $moneyWrapper;
    private TokenSignupHistoryRepository $tokenSignupHistoryRepository;
    private TokenSignupBonusConfig $tokenSignupBonusConfig;

    public function __construct(
        EntityManagerInterface $entityManager,
        BalanceHandlerInterface $balanceHandler,
        TokenManagerInterface $tokenManager,
        TranslatorInterface $translator,
        MoneyWrapperInterface $moneyWrapper,
        TokenSignupHistoryRepository $tokenSignupHistoryRepository,
        TokenSignupBonusConfig $tokenSignupBonusConfig
    ) {
        $this->em = $entityManager;
        $this->balanceHandler = $balanceHandler;
        $this->tokenManager = $tokenManager;
        $this->translator = $translator;
        $this->moneyWrapper = $moneyWrapper;
        $this->tokenSignupHistoryRepository = $tokenSignupHistoryRepository;
        $this->tokenSignupBonusConfig = $tokenSignupBonusConfig;
    }

    public function createTokenSignupBonusLink(
        Token $token,
        Money $bonusAmount,
        int $participants
    ): TokenSignupBonusCode {
        if ($token->getSignUpBonusCode()) {
            throw new ApiBadRequestException();
        }

        $user = $token->getOwner();

        $userTokenAvailable = $this->tokenManager->getRealBalance(
            $token,
            $this->balanceHandler->balance($user, $token),
            $user
        )->getAvailable();

        $reward = $this->getBonusReward(
            $bonusAmount,
            (string)$participants
        );
        $lockedAmount = $reward->multiply($participants);

        if ($bonusAmount->lessThan($this->tokenSignupBonusConfig->getMinTokensAmount())
            || $bonusAmount->greaterThan($userTokenAvailable)
        ) {
            throw new ApiBadRequestException(
                $this->translator->trans('api.tokens.token_sign_up_bonus.invalid_amount')
            );
        }

        if ($participants < $this->tokenSignupBonusConfig->getMinParticipantsAmount()
            || $participants > $this->tokenSignupBonusConfig->getMaxParticipantsAmount()
        ) {
            throw new ApiBadRequestException(
                $this->translator->trans('api.tokens.token_sign_up_bonus.invalid_participants_amount')
            );
        }

        if ($reward->lessThan($this->tokenSignupBonusConfig->getMinTokenReward())) {
            throw new ApiBadRequestException($this->translator->trans(
                'api.tokens.token_sign_up_bonus.invalid_reward',
                [
                    '%tokenName%' => $token->getName(),
                    '%reward%' => $this->moneyWrapper->format(
                        $this->tokenSignupBonusConfig->getMinTokenReward(),
                        false
                    ),
                ]
            ));
        }

        if (!$userTokenAvailable->greaterThanOrEqual($lockedAmount)) {
            throw new ApiBadRequestException(
                $this->translator->trans(
                    "api.tokens.token_sign_up_bonus.not_enough_tokens",
                    ['token' => $token->getName()]
                )
            );
        }

        $code = bin2hex(random_bytes(32));

        $tokenSignUpBonusCode = (new TokenSignupBonusCode())
            ->setToken($token)
            ->setAmount($reward)
            ->setParticipants($participants)
            ->setCode($code)
            ->setLockedAmount($lockedAmount);

        $this->em->persist($tokenSignUpBonusCode);
        $this->em->flush();

        try {
            $this->balanceHandler->beginTransaction();
            $this->balanceHandler->withdraw($user, $token, $lockedAmount);
        } catch (Throwable $exception) {
            $this->balanceHandler->rollback();

            throw $exception;
        }

        return $tokenSignUpBonusCode;
    }

    public function deleteTokenSignupBonusLink(Token $token): void
    {
        $signUpBonusCode = $token->getSignUpBonusCode();

        if (!$signUpBonusCode) {
            throw new NotFoundHttpException(
                $this->translator->trans('api.tokens.token_sign_up_bonus.not_exists')
            );
        }

        $amountToReturn = $signUpBonusCode->getLockedAmount();

        if (!$amountToReturn->isPositive()) {
            return;
        }

        try {
            $this->balanceHandler->beginTransaction();
            $this->balanceHandler->update(
                $token->getOwner(),
                $token,
                $amountToReturn,
                self::CLAIM_TOKEN_SIGNUP_BONUS
            );

            $this->em->remove($signUpBonusCode);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->balanceHandler->rollback();

            throw $e;
        }
    }

    public function withdrawTokenSignupBonus(Token $token, User $user, Money $amount): void
    {
        $signUpBonusCode = $token->getSignUpBonusCode();

        if (!$signUpBonusCode) {
            throw new NotFoundHttpException(
                $this->translator->trans('api.tokens.token_sign_up_bonus.not_exists')
            );
        }

        $signUpBonusCode = $token->getSignUpBonusCode();

        $signUpBonusCode->setLockedAmount(
            $signUpBonusCode->getLockedAmount()->subtract($amount)
        );

        $signUpBonusCode->setParticipants(
            (int) $signUpBonusCode->getParticipants() - 1
        );

        $user->setReferencer($token->getOwner());

        $tokenSignUpBonusHistory = (new TokenSignupHistory())
            ->setToken($token)
            ->setUser($user)
            ->setAmount($amount->getAmount());

        $this->em->persist($user);
        $this->em->persist($signUpBonusCode);
        $this->em->persist($tokenSignUpBonusHistory);
        $this->em->flush();

        if (0 === $signUpBonusCode->getParticipants()) {
            $this->deleteTokenSignupBonusLink($token);
        }
    }

    public function claimTokenSignupBonus(Token $token, User $user, Money $amount): void
    {
        $tokenSignUpBonusHistory = $this->tokenSignupHistoryRepository
            ->findOneByUserAndToken($user, $token);

        if (!$tokenSignUpBonusHistory) {
            throw new NotFoundHttpException('Bonus code not found');
        }

        try {
            $this->balanceHandler->beginTransaction();
            $this->balanceHandler->update(
                $user,
                $token,
                $amount,
                'claim_token_signup_bonus'
            );

            $tokenSignUpBonusHistory->setStatus(TokenSignupHistory::DELIVERED_STATUS);

            $this->em->persist($tokenSignUpBonusHistory);
            $this->em->flush();
        } catch (Throwable $exception) {
            $this->balanceHandler->rollback();

            throw $exception;
        }
    }

    private function getBonusReward(Money $amount, string $participants): Money
    {
        $participantsObj = $this->moneyWrapper
            ->parse($participants, Symbols::TOK);

        $reward = $this->roundBonusReward(
            $amount->ratioOf($participantsObj)
        );

        return $this->moneyWrapper->parse(
            $reward,
            Symbols::TOK
        );
    }

    private function roundBonusReward(string $amount, int $precision = self::BONUS_REWARD_PRECISION): string
    {
        $dotPosition = intval(strpos($amount, '.'));

        if (0 !== $dotPosition) {
            $amount = substr($amount, 0, $dotPosition + $precision + 1);
        }

        return $amount;
    }
}
