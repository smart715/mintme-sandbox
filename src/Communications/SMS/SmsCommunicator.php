<?php declare(strict_types = 1);

namespace App\Communications\SMS;

use App\Communications\SMS\Config\SmsConfig;
use App\Communications\SMS\Exception\BlacklistedCodeCountryException;
use App\Communications\SMS\Model\SMS;
use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Manager\BlacklistManagerInterface;

class SmsCommunicator
{
    private const D7_PROVIDER = 'd7';
    private const CLICKATELL_PROVIDER = 'clickatell';
    public const CLICKATELL_PRIORITY_CODES = [
        SMS::USA_COUNTRY_CODE,
    ];

    private UserActionLogger $userActionLogger;
    private SmsConfig $smsConfig;
    private SmsCommunicatorInterface $d7NetworksCommunicator;
    private SmsCommunicatorInterface $clickAtellCommunicator;
    private BlacklistManagerInterface $blacklistManager;

    public function __construct(
        UserActionLogger $userActionLogger,
        SmsConfig $smsConfig,
        D7NetworksCommunicator $d7NetworksCommunicator,
        ClickAtellCommunicator $clickAtellCommunicator,
        BlacklistManagerInterface $blacklistManager
    ) {
        $this->userActionLogger = $userActionLogger;
        $this->smsConfig = $smsConfig;
        $this->d7NetworksCommunicator = $d7NetworksCommunicator;
        $this->clickAtellCommunicator = $clickAtellCommunicator;
        $this->blacklistManager = $blacklistManager;
    }

    public function send(SMS $sms, ?User $user): ?string
    {
        $sentBy = null;

        $providers = in_array($sms->getCountryCode(), self::CLICKATELL_PRIORITY_CODES)
            ? $this->changeProvidersToClickatellPriority($this->smsConfig->getProviders())
            : $this->changeProvidersPriority($this->smsConfig->getProviders(), $user);

        foreach ($providers as $provider) {
            $providerName = $provider['name'];
            $retry = (int) $provider['retry'];
            $phoneNumber = $user->getProfile()->getPhoneNumber();

            if ($phoneNumber &&
                $this->blacklistManager->isBlacklistedCodeCountry($phoneNumber->getPhoneNumber(), $providerName)
            ) {
                throw new BlacklistedCodeCountryException();
            }

            try {
                $sender = $this->getProviderSender($providerName);

                if (!$sender) {
                    throw new \Exception('Provider '.$providerName.' not found');
                }

                while ($retry > 0) {
                    try {
                        $response = $sender->send($sms);

                        if (isset($response['error']) && $response['error']) {
                            $error = json_encode($response);

                            throw new \Exception((string ) $error);
                        }

                        $sentBy = $providerName;
                        $this->setUserActionLogRequest(['to' => $sms->getTo(), 'provider' => $providerName, $response]);

                        break 2;
                    } catch (\Throwable $e) {
                        $this->setUserActionLogError($e, $providerName, $retry);
                        $retry--;
                    }
                }
            } catch (\Throwable $e) {
                $this->setUserActionLogError($e, $providerName, $retry);
            }
        }

        return $sentBy;
    }

    private function getProviderSender(string $providerName): ?SmsCommunicatorInterface
    {
        if (self::D7_PROVIDER === $providerName) {
            return $this->d7NetworksCommunicator;
        } elseif (self::CLICKATELL_PROVIDER === $providerName) {
            return $this->clickAtellCommunicator;
        }

        return null;
    }

    private function changeProvidersPriority(array $providers, ?User $user): array
    {
        $phoneNumber = $user->getProfile()->getPhoneNumber();

        if (!$phoneNumber) {
            return $providers;
        }

        $highPriority = [];
        $lowPriority = [];

        foreach ($providers as $provider) {
            if ($provider['name'] !== $phoneNumber->getProvider() && !$phoneNumber->isVerified()) {
                $highPriority[] = $provider;
            } else {
                $lowPriority[] = $provider;
            }
        }

        return array_merge($highPriority, $lowPriority);
    }

    private function changeProvidersToClickatellPriority(array $providers): array
    {
        usort($providers, static fn($a, $b) => $a['name'] === self::CLICKATELL_PROVIDER ? -1 : 1);

        return $providers;
    }

    private function setUserActionLogRequest(array $data): void
    {
        $this->userActionLogger->info(
            'Phone number verification code requested.',
            $data
        );
    }

    private function setUserActionLogError(\Throwable $e, string $providerName, int $retry): void
    {
        $this->userActionLogger->error(
            $e->getMessage()
        );
    }
}
