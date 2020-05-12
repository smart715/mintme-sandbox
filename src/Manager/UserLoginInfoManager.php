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

class UserLoginInfoManager implements UserLoginInfoInterface
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
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $request = $event->getRequest();
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $deviceDetector = new DeviceDetector($userAgent);
        $deviceDetector->parse();
        $clientInfo = $deviceDetector->getClient();
        $osInfo = $deviceDetector->getOs();
        $deviceType = $deviceDetector->getDeviceName();

        $deviceIp= 'unknown' === $request->getClientIp() ?
            $_SERVER['REMOTE_ADDR'] :
            $request->getClientIp();
        $deviceInfo = ucwords($deviceType).' - '.$clientInfo['name'].'V'.$clientInfo['version'];
        $deviceOs = $osInfo['name'];

        /** @var UserLoginInfoRepository */
        $repository = $this->em->getRepository(UserLoginInfo::class);

        if (!$repository->getStoreUserDeviceInfo($user, $deviceIp)) {
            $userLoginInfo = new UserLoginInfo($user);
            $userLoginInfo->setIpAddress($deviceIp);
            $userLoginInfo->setDeviceInfo($deviceInfo);
            $userLoginInfo->setOsInfo($deviceOs);
            $this->em->persist($userLoginInfo);
            $this->em->flush();

            $this->eventDispatcher->dispatch(
                new NewDeviceDetectedEvent($user, $userLoginInfo),
                NewDeviceDetectedEvent::NAME
            );
        }
    }
}
