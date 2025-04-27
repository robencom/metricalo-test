<?php

namespace App\Payment\Mapper;

use App\Payment\DTO\PaymentResponse;

class AciResponseMapper implements ResponseMapperInterface
{
    public function map(array $data): PaymentResponse
    {
        $resp = new PaymentResponse();
        $resp->transactionId = $data['id'];

        $resp->createdAt = $data['timestamp'];

        $resp->amount = ((float) $data['amount']);
        $resp->currency = $data['currency'];

        $resp->cardBin = $data['card']['bin'] ?? '';

        return $resp;
    }
}