<?php
namespace App\Payment\DTO;

/**
 * Unified response mapping from any Acquirer (Shift4 or ACI).
 */
class PaymentResponse
{
    public string $transactionId;

    public string $createdAt;

    public float $amount;

    public string $currency;

    public string $cardBin;
}
