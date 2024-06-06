<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserNotificationConfig;
use App\Repository\UserNotificationConfigRepository;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;

class UserNotificationConfigManager implements UserNotificationConfigManagerInterface
{
    private EntityManagerInterface $em;
    private UserNotificationConfigRepository $userNotificationConfigRepository;
    private TokenManagerInterface $tokenManager;
    private TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $em,
        UserNotificationConfigRepository $userNotificationConfigRepository,
        TranslatorInterface $translator,
        TokenManagerInterface $tokenManager
    ) {
        $this->em = $em;
        $this->userNotificationConfigRepository = $userNotificationConfigRepository;
        $this->translator = $translator;
        $this->tokenManager = $tokenManager;
    }

    public function getUserNotificationsConfig(User $user): ?array
    {
        $notificationTypes = NotificationTypes::getConfigurable();
        $notificationChannels = NotificationChannels::getAll();

        $userNotificationConfig = $this->userNotificationConfigRepository->getUserNotificationsConfig($user);
        $defaultConfig = [];

        foreach ($notificationTypes as $nType) {
            $defaultConfig[$nType]['text'] = $this->translator->trans('userNotification.type.'.$nType);
            $defaultConfig[$nType]['show'] = true;

            if (NotificationTypes::NEW_INVESTOR === $nType && !$user->getProfile()->hasTokens()) {
                $defaultConfig[$nType]['show'] = false;
            } elseif (NotificationTypes::TOKEN_MARKETING_TIPS === $nType && !$user->getProfile()->hasTokens()) {
                $defaultConfig[$nType]['show'] = false;
            }

            foreach ($notificationChannels as $nChannel) {
                if (NotificationChannels::ADVANCED === $nChannel) {
                    continue;
                }

                $defaultConfig[$nType]['channels'][$nChannel]['text'] = ucfirst($nChannel);
                $defaultConfig[$nType]['channels'][$nChannel]['value'] = false;
            }
        }

        $tokensSettings = [];

        foreach ($userNotificationConfig as $unc) {
            $type = $unc->getType();
            $channel = $unc->getChannel();
            $token = $unc->getToken();

            if ($this->isTokenNewPostNotificationConfig($type, $channel) && $token) {
                $tokensSettings[] = [
                    'name' => $token->getName(),
                    'image' => $token->getImage(),
                    'value' => $unc->getTokenPostEnabled(),
                ];
            } else {
                $defaultConfig[$type]['channels'][$channel]['text'] = ucfirst($channel);
                $defaultConfig[$type]['channels'][$channel]['value'] = true;
            }
        }

        $defaultConfig[NotificationTypes::TOKEN_NEW_POST]['channels'][NotificationChannels::ADVANCED] = $tokensSettings;

        return $defaultConfig;
    }

    public function updateUserNotificationsConfig(
        User $user,
        array $configToStore
    ): void {

        $userConfigStored = $this->userNotificationConfigRepository->getUserNotificationsConfig($user);

        if ($userConfigStored) {
            foreach ($userConfigStored as $userConfig) {
                $this->userNotificationConfigRepository->deleteUserNotificationsConfig($userConfig->getId());
            }
        }

        foreach ($configToStore as $type => $nConfig) {
            foreach ($nConfig['channels'] as $channel => $channelConfig) {
                if ($this->isTokenNewPostNotificationConfig($type, $channel)) {
                    foreach ($channelConfig as $token) {
                        $tokenInfo = $this->tokenManager->findByName($token['name']);

                        if (!$tokenInfo) {
                            continue;
                        }

                        $newConfig = $this->createTokenNewPostNotificationConfig(
                            $type,
                            $channel,
                            $user,
                            $tokenInfo,
                            $token['value'],
                        );
                        $this->em->persist($newConfig);
                    }
                } elseif ($channelConfig['value']) {
                    $newConfig = (new UserNotificationConfig())
                        ->setType($type)
                        ->setChannel($channel)
                        ->setUser($user);
                    $this->em->persist($newConfig);
                }
            }
        }

        $this->em->flush();
    }

    public function initializeUserNotificationConfig(User $user): void
    {
        $notificationTypes = NotificationTypes::getConfigurable();
        $notificationChannels = NotificationChannels::getAll();

        foreach ($notificationTypes as $nType) {
            foreach ($notificationChannels as $nChannel) {
                $newConfig = (new UserNotificationConfig())
                    ->setType($nType)
                    ->setChannel($nChannel)
                    ->setUser($user);
                $this->em->persist($newConfig);
            }
        }

        $this->em->flush();
    }

    public function getOneUserNotificationConfig(User $user, string $type, string $channel): array
    {
        return $this->userNotificationConfigRepository->getOneUserNotificationsConfig($user, $type, $channel);
    }


    public function isAllowedToSendNotification(User $user, Token $token): bool
    {
        $disabledConfig = $this->userNotificationConfigRepository
            ->getDisabledUserPostNotificationConfigByToken($user, $token);

        return empty($disabledConfig);
    }

    private function isTokenNewPostNotificationConfig(string $notificationType, string $channel): bool
    {
        return NotificationTypes::TOKEN_NEW_POST === $notificationType
            && NotificationChannels::ADVANCED === $channel;
    }

    private function createTokenNewPostNotificationConfig(
        string $type,
        string $channel,
        User $user,
        Token $token,
        bool $notificationConfigValue
    ): UserNotificationConfig {
        return (new UserNotificationConfig())
            ->setType($type)
            ->setChannel($channel)
            ->setUser($user)
            ->setToken($token)
            ->setTokenPostEnabled($notificationConfigValue);
    }
}
