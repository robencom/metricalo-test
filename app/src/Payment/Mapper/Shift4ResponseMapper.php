<?php

namespace App\Payment\Mapper;

use App\Payment\DTO\PaymentResponse;
use DateTimeImmutable;
use DateTimeInterface;

class Shift4ResponseMapper implements ResponseMapperInterface
{
    public function map(array $data): PaymentResponse
    {
        $resp = new PaymentResponse();
        $resp->transactionId = $data['id'];

        $timestamp = (int) $data['created'];
        $resp->createdAt = (new DateTimeImmutable("@{$timestamp}"))
            ->format(DateTimeInterface::ATOM);

        $resp->amount = $data['amount'] / 100; //(convert minor to major)
        $resp->currency = $data['currency'];

        $resp->cardBin = $data['card']['first6'] ?? '';

        return $resp;
    }
}