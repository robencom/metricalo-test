<?php
namespace App\Tests\Service;

use App\Payment\Adapter\PaymentAdapterInterface;
use App\Payment\DTO\PaymentRequest;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Exception\PaymentException;
use App\Payment\Service\PaymentService;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class PaymentServiceTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testProcessUnknownProvider(): void
    {
        $service = new PaymentService([]);
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage("Unknown Merchant Gateway 'foo'");
        $service->process('foo', new PaymentRequest());
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    public function testProcessDelegatesToCorrectAdapter(): void
    {
        $req = new PaymentRequest();
        $req->amount = 100;
        $req->currency = 'EUR';
        $req->cardNumber = '4111111111111111';
        $req->cardExpMonth = 12;
        $req->cardExpYear = 2027;
        $req->cardCvv = '123';

        // Adapter "foo" returns ID "X"
        $fooAdapter = $this->getMockBuilder(PaymentAdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMockClassName('FooAdapter')
            ->onlyMethods(['process'])
            ->getMock();
        $fooAdapter->expects($this->once())
            ->method('process')
            ->with($req)
            ->willReturn($this->makeResponse('X'));

        // Adapter "bar" returns ID "Y"
        $barAdapter = $this->getMockBuilder(PaymentAdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMockClassName('BarAdapter')
            ->onlyMethods(['process'])
            ->getMock();
        $barAdapter->expects($this->once())
            ->method('process')
            ->with($req)
            ->willReturn($this->makeResponse('Y'));

        // Give both adapters to the service under keys foo & bar
        $service = new PaymentService([$fooAdapter, $barAdapter]);

        // Calling foo
        $respFoo = $service->process('foo', $req);
        $this->assertSame('X', $respFoo->transactionId);

        // Calling bar
        $respBar = $service->process('bar', $req);
        $this->assertSame('Y', $respBar->transactionId);
    }

    private function makeResponse(string $id): PaymentResponse
    {
        $response = new PaymentResponse();
        $response->transactionId = $id;
        $response->createdAt = 'now';
        $response->amount = 1.00;
        $response->currency = 'EUR';
        $response->cardBin = '123456';
        return $response;
    }
}
