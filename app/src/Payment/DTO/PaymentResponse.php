<?php
namespace App\Payment\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaymentResponse',
    description: 'Unified acquirer response'
)]
class PaymentResponse
{
    #[OA\Property(example: 'tr_5Xz9aXDN')]
    public string $transactionId;

    #[OA\Property(format: 'date-time', example: '2025-04-27T10:34:12Z')]
    public string $createdAt;

    #[OA\Property(format: 'float', example: 199.99)]
    public float $amount;

    #[OA\Property(example: 'USD')]
    public string $currency;

    #[OA\Property(example: '424242')]
    public string $cardBin;
}
