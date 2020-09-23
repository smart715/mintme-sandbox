<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Entity\UserLoginInfo;
use App\Events\NewDeviceDetectedEvent;
use App\Repository\UserLoginInfoRepository;
use DeviceDetector\DeviceDetector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class UserLoginInfoManager implements UserLoginInfoManagerInterface
{

    /** @var EntityManagerInterface */
    private $em;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $em
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->em = $em;
    }

    public function updateUserDeviceLoginInfo(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $deviceDetector = new DeviceDetector($userAgent);
        $deviceDetector->parse();

        /** @var array $clientInfo */
        $clientInfo = $deviceDetector->getClient();
        $clientName = $clientInfo['name'] ?? 'Unknown';
        $clientVersion = $clientInfo['version'] ?? 'Unknown';

        $deviceType = $deviceDetector->getDeviceName();
        $deviceIp = 'unknown' === $request->getClientIp() ?
            $_SERVER['REMOTE_ADDR'] :
            $request->getClientIp();

        /** @var array $osInfo */
        $osInfo = $deviceDetector->getOs();
        $deviceInfo = ucwords($deviceType).' - '.$clientName.'V'.$clientVersion;
        $deviceOs = $osInfo['name'] ?? 'Unknown';

        /** @var UserLoginInfoRepository */
        $repository = $this->em->getRepository(UserLoginInfo::class);

        /**
         * @var User $user
         * @psalm-suppress UndefinedDocblockClass
         */
        $user = $event->getAuthenticationToken()->getUser();

        if (!$repository->getStoreUserDeviceInfo($user, $deviceIp)) {
            $userLoginInfo = new UserLoginInfo($user);
            $userLoginInfo->setIpAddress($deviceIp);
            $userLoginInfo->setDeviceInfo($deviceInfo);
            $userLoginInfo->setOsInfo($deviceOs);
            $this->em->persist($userLoginInfo);
            $this->em->flush();

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new NewDeviceDetectedEvent($user, $userLoginInfo),
                NewDeviceDetectedEvent::NAME
            );
        }
    }
}
