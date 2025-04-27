<?php
namespace App\Payment\Service;

use App\Payment\Adapter\PaymentAdapterInterface;
use App\Payment\DTO\PaymentRequest;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Exception\PaymentException;

/**
 * Orchestrates routing between multiple Acquirers:
 * - Shift4 (Payment Gateway)
 * - ACI (Payment Gateway)
 */
class PaymentService
{
    /** @var array<string,PaymentAdapterInterface> */
    private array $adapters;

    /**
     * @throws \ReflectionException
     */
    public function __construct(iterable $adapters)
    {
        foreach ($adapters as $adapter) {
            $key = (new \ReflectionClass($adapter))->getShortName();

            $this->adapters[strtolower(str_replace('Adapter', '', $key))] = $adapter;
        }
    }

    /**
     * @throws PaymentException
     */
    public function process(string $provider, PaymentRequest $request): PaymentResponse
    {
        if (!isset($this->adapters[$provider])) {
            throw new PaymentException("Unknown Merchant Gateway '{$provider}'");
        }

        return $this->adapters[$provider]->process($request);
    }
}
