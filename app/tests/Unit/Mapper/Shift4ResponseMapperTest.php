<?php
namespace App\Tests\Mapper;

use App\Payment\DTO\PaymentResponse;
use App\Payment\Mapper\Shift4ResponseMapper;
use PHPUnit\Framework\TestCase;

class Shift4ResponseMapperTest extends TestCase
{
    public function testMap(): void
    {
        $data = [
            'id' => 'char_123',
            'created' => 1672531200, // 2023-01-01T00:00:00Z
            'amount' => 499,
            'currency'=> 'USD',
            'card' => ['first6' => '424242'],
        ];

        $mapper = new Shift4ResponseMapper();
        $resp = $mapper->map($data);

        $this->assertInstanceOf(PaymentResponse::class, $resp);
        $this->assertSame('char_123', $resp->transactionId);
        $this->assertSame('2023-01-01T00:00:00+00:00', $resp->createdAt);
        $this->assertSame(4.99, $resp->amount);
        $this->assertSame('USD', $resp->currency);
        $this->assertSame('424242', $resp->cardBin);
    }
}
