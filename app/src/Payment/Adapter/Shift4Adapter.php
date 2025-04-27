<?php
namespace App\Payment\Adapter;

use App\Payment\DTO\PaymentRequest;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Exception\PaymentException;
use App\Payment\Mapper\Shift4ResponseMapper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Talks to the Shift4 Payment Gateway in test mode.
 */
class Shift4Adapter implements PaymentAdapterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $authKey,
        private readonly string $mid,
        private readonly string $endpointUrl,
        private readonly Shift4ResponseMapper $shift4Mapper,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function process(PaymentRequest $request): PaymentResponse
    {
        $minorAmount = (int) round($request->amount * 100);

        $response = $this->http->request('POST', $this->endpointUrl, [
            'auth_basic' => [$this->authKey, ''],
            'json' => [
                'amount' => $minorAmount,
                'currency' => $request->currency,
                'merchantAccountId' => $this->mid,
                'card' => [
                    'number' => $request->cardNumber,
                    'expMonth' => $request->cardExpMonth,
                    'expYear' => $request->cardExpYear,
                    'cvc' => $request->cardCvv,
                ],
            ],
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $this->logger->error('Gateway error', [
                'gateway' => 'shift4',
                'status' => $response->getStatusCode(),
                'body' => $response->getContent(false),
            ]);
            throw new PaymentException('Shift4 declined the transaction');
        }

        return $this->shift4Mapper->map($response->toArray());
    }
}
