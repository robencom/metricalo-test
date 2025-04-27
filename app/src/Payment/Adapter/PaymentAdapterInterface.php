<?php
namespace App\Payment\Adapter;

use App\Payment\DTO\PaymentRequest;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Exception\PaymentException;

/**
 * All Payment Gateways (Acquirers) must implement this.
 */
interface PaymentAdapterInterface
{
    /**
     * Send card-present or server-to-server debit request
     * via this Acquirer, return unified DTO.
     *
     * @throws PaymentException
     */
    public function process(PaymentRequest $request): PaymentResponse;
}
