<?php
namespace App\Tests\Command;

use App\Payment\DTO\PaymentResponse;
use App\Payment\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ProcessPaymentCommandTest extends KernelTestCase
{
    public function testMissingAciHolderFails(): void
    {
        self::bootKernel();
        $app = new Application(self::$kernel);
        $cmd = $app->find('app:process-payment');
        $tester = new CommandTester($cmd);

        $tester->execute([
            'provider' => 'aci',
            'amount' => 1000,
            'currency' => 'EUR',
            'cardNumber' => '4200000000000000',
            'expMonth' => 12,
            'expYear' => 2028,
            'cvv' => '123',
        ]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('cardHolderName', $tester->getDisplay());
    }

    public function testSuccessfulShift4Command(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Build a PaymentResponse instance with known values
        $fakeResponse = new PaymentResponse();
        $fakeResponse->transactionId = 'x';
        $fakeResponse->createdAt = 'now';
        $fakeResponse->amount = 1.00;
        $fakeResponse->currency = 'USD';
        $fakeResponse->cardBin = '123456';

        // Mock the PaymentService so process() always returns our fakeResponse
        $mock = $this->getMockBuilder(PaymentService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['process'])
            ->getMock();
        $mock->method('process')
            ->willReturn($fakeResponse);

        // Override the real service in the container
        $container->set(PaymentService::class, $mock);

        // Now run the command
        $application = new Application(self::$kernel);
        $command = $application->find('app:process-payment');
        $tester = new CommandTester($command);
        $tester->execute([
            'provider' => 'shift4',
            'amount' => 100,
            'currency' => 'USD',
            'cardNumber' => '4111111111111111',
            'expMonth' => 12,
            'expYear' => 2027,
            'cvv' => '321',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('"transactionId":"x"', $tester->getDisplay());
    }
}
