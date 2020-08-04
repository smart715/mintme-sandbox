<?php declare(strict_types = 1);

namespace App\Utils\Facebook;

use App\Entity\Profile;
use FacebookAds\Api;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;

class FacebookPixelCommunicator implements FacebookPixelCommunicatorInterface
{
    /** @var string */
    private $appSecret;
    
    /** @var string */
    private $appID;
    
    /** @var string */
    private $pixelID;
    
    /** @var string */
    private $accessToken;
    
    /** @var bool */
    private $useTestCode;
    
    /** @var string */
    private $testCode;
    
    public function __construct(
        string $appSecret,
        string $appID,
        string $pixelID,
        string $accessToken,
        bool $useTestCode,
        string $testCode
    ) {
        $this->appSecret = $appSecret;
        $this->appID = $appID;
        $this->pixelID = $pixelID;
        $this->accessToken = $accessToken;
        $this->useTestCode = $useTestCode;
        $this->testCode = $testCode;
    }
    
    public function sendUserEvent(
        string $eventName,
        string $userEmail,
        string $userIP,
        string $userUserAgent,
        array $params,
        ?Profile $profile
    ): void {
        Api::init($this->appID, $this->appSecret, $this->accessToken);
    
        $userData = (new UserData())
            ->setClientUserAgent($userUserAgent)
            ->setClientIpAddress($userIP)
            ->setEmail($userEmail);
    
        if ($profile) {
            $userData->setCity($profile->getCity() ?? '');
            $userData->setZipCode($profile->getZipCode() ?? '');
            $userData->setFirstName($profile->getFirstName() ?? '');
            $userData->setLastName($profile->getLastName() ?? '');
        }
    
        $customData = (new CustomData());
        
        foreach ($params as $key => $param) {
            $customData->addCustomProperty($key, $param);
        }
    
        /** @phpstan-ignore-next-line */
        $event = (new Event())
            ->setEventName($eventName)
            ->setEventTime(time())
            ->setUserData($userData)
            ->setCustomData($customData);
    
        $events = array();
        array_push($events, $event);
    
        if ($this->useTestCode) {
            /** @phpstan-ignore-next-line */
            $request = (new EventRequest($this->pixelID))->setEvents($events)->setTestEventCode($this->testCode);
            $request->execute();
        } else {
            /** @phpstan-ignore-next-line */
            $request = (new EventRequest($this->pixelID))->setEvents($events);
            $request->execute();
        }
    }
    
    public function sendEvent(
        string $eventName,
        string $userEmail,
        array $params,
        ?Profile $profile
    ): void {
        Api::init($this->appID, $this->appSecret, $this->accessToken);
    
        $userData = (new UserData())
            ->setEmail($userEmail);
    
        if ($profile) {
            $userData->setCity($profile->getCity() ?? '');
            $userData->setZipCode($profile->getZipCode() ?? '');
            $userData->setFirstName($profile->getFirstName() ?? '');
            $userData->setLastName($profile->getLastName() ?? '');
        }
    
        $customData = (new CustomData());
    
        $customData->setValue($params['amount'] ?? '');
        $customData->setCurrency($params['currency'] ?? '');
    
        /** @phpstan-ignore-next-line */
        $event = (new Event())
            ->setEventName($eventName)
            ->setEventTime(time())
            ->setUserData($userData)
            ->setCustomData($customData);
    
        $events = array();
        array_push($events, $event);
    
        if ($this->useTestCode) {
            /** @phpstan-ignore-next-line */
            $request = (new EventRequest($this->pixelID))->setEvents($events)->setTestEventCode($this->testCode);
            $request->execute();
        } else {
            /** @phpstan-ignore-next-line */
            $request = (new EventRequest($this->pixelID))->setEvents($events);
            $request->execute();
        }
    }
}
