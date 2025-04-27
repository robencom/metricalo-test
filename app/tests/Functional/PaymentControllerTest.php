<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    private function makeRequest(string $provider, array $payload): KernelBrowser
    {
        $client = static::createClient();
        $client->request(
            'POST',
            "/api/payment/{$provider}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        return $client;
    }

    public function testInvalidAmount(): void
    {
        $client = $this->makeRequest('shift4', [
            'amount' => 0,
            'currency' => 'EUR',
            'cardNumber' => '4111111111111111',
            'cardExpMonth' => 12,
            'cardExpYear' => 2027,
            'cardCvv' => '123',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $errors = json_decode($client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString('amount:', $errors[0]);
    }

    public function testInvalidCurrency(): void
    {
        $client = $this->makeRequest('shift4', [
            'amount' => 10.00,
            'currency' => 'YEN',
            'cardNumber' => '4111111111111111',
            'cardExpMonth' => 12,
            'cardExpYear' => 2027,
            'cardCvv' => '123',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $errors = json_decode($client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString('currency:', $errors[0]);
    }

    public function testInvalidCardNumber(): void
    {
        $client = $this->makeRequest('shift4', [
            'amount' => 10.00,
            'currency' => 'EUR',
            'cardNumber' => '1234',
            'cardExpMonth' => 12,
            'cardExpYear' => 2027,
            'cardCvv' => '123',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $errors = json_decode($client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString('cardNumber:', $errors[0]);
    }

    public function testInvalidMonth(): void
    {
        $client = $this->makeRequest('shift4', [
            'amount' => 10.00,
            'currency' => 'EUR',
            'cardNumber' => '4111111111111111',
            'cardExpMonth' => 13,
            'cardExpYear' => 2027,
            'cardCvv' => '123',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $errors = json_decode($client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString('cardExpMonth:', $errors[0]);
    }

    public function testExpiredDate(): void
    {
        // Now is April 2025, so March (3) in 2025 is expired
        $client = $this->makeRequest('shift4', [
            'amount' => 10.00,
            'currency' => 'EUR',
            'cardNumber' => '4111111111111111',
            'cardExpMonth' => 3,
            'cardExpYear' => 2025,
            'cardCvv' => '123',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $errors = json_decode($client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString('cardExpMonth:', $errors[0]);
    }

    public function testCardInVeryFutureDate(): void
    {
        $client = $this->makeRequest('shift4', [
            'amount' => 10.00,
            'currency' => 'EUR',
            'cardNumber' => '4111111111111111',
            'cardExpMonth' => 3,
            'cardExpYear' => 2099,
            'cardCvv' => '123',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $errors = json_decode($client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString('cardExpYear:', $errors[0]);
    }

    public function testInvalidCvv(): void
    {
        $client = $this->makeRequest('shift4', [
            'amount' => 10.00,
            'currency' => 'EUR',
            'cardNumber' => '4111111111111111',
            'cardExpMonth' => 12,
            'cardExpYear' => 2027,
            'cardCvv' => '12',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $errors = json_decode($client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString('cardCvv:', $errors[0]);
    }

    public function testMissingHolderNameForAci(): void
    {
        $client = $this->makeRequest('aci', [
            'amount' => 10.00,
            'currency' => 'EUR',
            'cardNumber' => '4200000000000000',
            'cardExpMonth' => 12,
            'cardExpYear' => 2027,
            'cardCvv' => '123',
            // no cardHolderName (only ACI)
        ]);

        $this->assertResponseStatusCodeSame(400);
        $errors = json_decode($client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString('cardHolderName:', $errors[0]);
    }
}
