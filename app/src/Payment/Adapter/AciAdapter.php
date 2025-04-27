<?php
namespace App\Payment\Adapter;

use App\Payment\DTO\PaymentRequest;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Exception\PaymentException;
use App\Payment\Mapper\AciResponseMapper;
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
 * Talks to the ACI server-to-server debit endpoint.
 */
class AciAdapter implements PaymentAdapterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $authKey,
        private readonly string $entityId,
        private readonly string $paymentBrand,
        private readonly string $endpointUrl,
        private readonly AciResponseMapper $aciMapper,
    ) {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function process(PaymentRequest $request): PaymentResponse
    {
        try {
            $response = $this->http->request('POST', $this->endpointUrl, [
                'headers' => [
                    'Authorization' => "Bearer {$this->authKey}",
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query([
                    'entityId' => $this->entityId,
                    'amount' => number_format($request->amount, 2, '.', ''),
                    'currency' => $request->currency,
                    'paymentType' => 'DB',
                    'paymentBrand' => $this->paymentBrand,
                    'card.number' => $request->cardNumber,
                    'card.holder' => $request->cardHolderName ?? '',
                    'card.expiryMonth'=> str_pad((string)$request->cardExpMonth, 2, '0', STR_PAD_LEFT),
                    'card.expiryYear' => (string)$request->cardExpYear,
                    'card.cvv' => $request->cardCvv,
                ]),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('HTTP error contacting ACI', [
                'exception' => $e->getMessage(),
            ]);
            throw new PaymentException('Payment gateway unavailable, please try again later');
        }

        $status = $response->getStatusCode();

        if ($status === 429) {
            throw new PaymentException('Rate limit exceeded, please wait before retrying');
        }

        if (Response::HTTP_OK !== $status) {
            $this->logger->error('Gateway error', [
                'gateway' => 'aci',
                'status' => $status,
                'request' => [
                    'amount' => number_format($request->amount, 2, '.', ''),
                    'currency' => $request->currency,
                    'card' => [
                        'number' => '****'.substr($request->cardNumber, -4),
                        'cvc' => '***',
                    ],
                ],
                'response' => $response->getContent(false),
            ]);
            throw new PaymentException('ACI declined the transaction');
        }

        return $this->aciMapper->map($response->toArray());
    }
}
