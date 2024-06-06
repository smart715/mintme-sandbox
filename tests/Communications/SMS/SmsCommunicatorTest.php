<?php declare(strict_types = 1);

namespace App\Tests\Communications\SMS;

use App\Communications\SMS\ClickAtellCommunicator;
use App\Communications\SMS\Config\SmsConfig;
use App\Communications\SMS\D7NetworksCommunicator;
use App\Communications\SMS\Model\SMS;
use App\Communications\SMS\SmsCommunicator;
use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Manager\BlacklistManagerInterface;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

class SmsCommunicatorTest extends TestCase
{
    private const TO = '+123456789';

    public function testSendSuccessfully(): void
    {
        $retries = 4;
        $smsProviders = [
            'clickatell' => ['name' => 'clickatell', 'enabled' => true, 'priority' => 2, 'retry' => $retries],
        ];

        $communicator = new SmsCommunicator(
            $this->mockUserActionLogger($this->once(), $this->never()),
            $this->mockSmsConfig($smsProviders),
            $this->mockD7NetworksCommunicator(),
            $this->mockClickAtellCommunicator(['success' => 'success'], 1),
            $this->createMock(BlacklistManagerInterface::class)
        );

        $sendBy = $communicator->send($this->mockSMS(self::TO), $this->mockUser());

        $this->assertEquals('clickatell', $sendBy);
    }

    public function testSendWithSameProviderUserHave(): void
    {
        $retries = 4;
        $smsProviders = [
            'clickatell' => ['name' => 'clickatell', 'enabled' => true, 'priority' => 2, 'retry' => $retries],
        ];

        $communicator = new SmsCommunicator(
            $this->mockUserActionLogger($this->once(), $this->never()),
            $this->mockSmsConfig($smsProviders),
            $this->mockD7NetworksCommunicator(),
            $this->mockClickAtellCommunicator(['success' => 'success'], 1),
            $this->createMock(BlacklistManagerInterface::class)
        );

        $sendBy = $communicator->send($this->mockSMS(self::TO), $this->mockUser('clickatell'));

        $this->assertEquals('clickatell', $sendBy);
    }

    public function testSendWithUnverifiedUser(): void
    {
        $retries = 4;
        $smsProviders = [
            'clickatell' => ['name' => 'clickatell', 'enabled' => true, 'priority' => 2, 'retry' => $retries],
        ];

        $communicator = new SmsCommunicator(
            $this->mockUserActionLogger($this->once(), $this->never()),
            $this->mockSmsConfig($smsProviders),
            $this->mockD7NetworksCommunicator(),
            $this->mockClickAtellCommunicator(['success' => 'success'], 1),
            $this->createMock(BlacklistManagerInterface::class)
        );

        $sendBy = $communicator->send($this->mockSMS(self::TO), $this->mockUser('', false));

        $this->assertEquals('clickatell', $sendBy);
    }

    public function testSendWithNonRegisteredProviderWillNotProceed(): void
    {
        $smsProviders = [
            'test' => ['name' => 'test', 'enabled' => true, 'priority' => 3, 'retry' => 3],
        ];

        $communicator = new SmsCommunicator(
            $this->mockUserActionLogger($this->never(), $this->once()),
            $this->mockSmsConfig($smsProviders),
            $this->mockD7NetworksCommunicator(),
            $this->mockClickAtellCommunicator(),
            $this->createMock(BlacklistManagerInterface::class)
        );

        $sendBy = $communicator->send($this->mockSMS(self::TO), $this->mockUser());

        $this->assertEquals(null, $sendBy);
    }

    public function testSendWithNoProviderWillSkipTheMethod(): void
    {
        $smsProviders = [];

        $communicator = new SmsCommunicator(
            $this->mockUserActionLogger($this->never(), $this->never()),
            $this->mockSmsConfig($smsProviders),
            $this->mockD7NetworksCommunicator(),
            $this->mockClickAtellCommunicator(),
            $this->createMock(BlacklistManagerInterface::class)
        );

        $sendBy = $communicator->send($this->mockSMS(self::TO), $this->mockUser('', true, 1));

        $this->assertEquals(null, $sendBy);
    }

    public function testSendWillLogAnErrorIfResponseHasError(): void
    {
        $retries = 3;
        $smsProviders = [
            'd7' => ['name' => 'd7', 'enabled' => true, 'priority' => 2, 'retry' => $retries],
        ];

        $communicator = new SmsCommunicator(
            $this->mockUserActionLogger($this->never(), $this->exactly($retries)),
            $this->mockSmsConfig($smsProviders),
            $this->mockD7NetworksCommunicator(['error' => 'error'], $retries),
            $this->mockClickAtellCommunicator(),
            $this->createMock(BlacklistManagerInterface::class)
        );

        $communicator->send($this->mockSMS(self::TO), $this->mockUser());
    }

    private function mockUserActionLogger(InvokedCount $infoCount, InvokedCount $errorCount): UserActionLogger
    {
        $userActionLogger = $this->createMock(UserActionLogger::class);
        $userActionLogger->expects($infoCount)->method('info');
        $userActionLogger->expects($errorCount)->method('error');

        return $userActionLogger;
    }

    private function mockSmsConfig(array $providers = []): SmsConfig
    {
        $smsConfig = $this->createMock(SmsConfig::class);
        $smsConfig->expects($this->once())->method('getProviders')->willReturn($providers);

        return $smsConfig;
    }

    private function mockD7NetworksCommunicator(?array $response = null, int $retries = 0): D7NetworksCommunicator
    {
        $d7NetworksCommunicator = $this->createMock(D7NetworksCommunicator::class);
        $d7NetworksCommunicator->expects($this->exactly($retries))
            ->method('send')
            ->willReturn($response ?: []);

        return $d7NetworksCommunicator;
    }

    private function mockClickAtellCommunicator(?array $response = null, int $retries = 0): ClickAtellCommunicator
    {
        $clickAtellCommunicator = $this->createMock(ClickAtellCommunicator::class);
        $clickAtellCommunicator->expects($this->exactly($retries))
            ->method('send')
            ->willReturn($response ?: []);

        return $clickAtellCommunicator;
    }

    private function mockSMS(string $TO): SMS
    {
        $sms = $this->createMock(SMS::class);
        $sms->method('getTo')->willReturn($TO);

        return $sms;
    }

    private function mockUser(string $provider = '', bool $isVerified = true, int $getProfileTimes = 2): User
    {
        $user = $this->createMock(User::class);
        $user->expects($this->exactly($getProfileTimes))
            ->method('getProfile')
            ->willReturn($this->mockProfile($provider, $isVerified, $getProfileTimes));

        return $user;
    }

    private function mockProfile(string $provider, bool $isVerified, int $getPhoneNumberTimes): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->expects($this->exactly($getPhoneNumberTimes))
            ->method('getPhoneNumber')
            ->willReturn($this->mockPhoneNumber($provider, $isVerified));

        return $profile;
    }

    private function mockPhoneNumber(string $provider, bool $isVerified): PhoneNumber
    {
        $phoneNumber = $this->createMock(PhoneNumber::class);
        $phoneNumber->method('getProvider')->willReturn($provider);
        $phoneNumber->method('isVerified')->willReturn($isVerified);

        return $phoneNumber;
    }
}
