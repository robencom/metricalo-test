<?php
namespace App\Tests\Mapper;

use App\Payment\DTO\PaymentResponse;
use App\Payment\Mapper\AciResponseMapper;
use PHPUnit\Framework\TestCase;

class AciResponseMapperTest extends TestCase
{
    public function testMap(): void
    {
        $data = [
            'id' => 'pay_456',
            'timestamp' => '2025-04-26 10:03:10+0000',
            'amount' => '92.00',
            'currency' => 'EUR',
            'card' => ['bin'=>'420000'],
        ];

        $mapper = new AciResponseMapper();
        $resp = $mapper->map($data);

        $this->assertInstanceOf(PaymentResponse::class, $resp);
        $this->assertSame('pay_456', $resp->transactionId);
        $this->assertSame('2025-04-26 10:03:10+0000', $resp->createdAt);
        $this->assertSame(92.00, $resp->amount);
        $this->assertSame('EUR', $resp->currency);
        $this->assertSame('420000', $resp->cardBin);
    }
}
