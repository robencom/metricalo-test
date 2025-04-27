# Payment-Test

A Symfony 6.4 test project (for Metricalo) demonstrating a unified response card-payment API and CLI, integrating:

- **Shift4** and **ACI** payment gateways
- Domain-specific terminology: Cardholder, Merchant, Issuing Bank, Acquiring Bank, Payment Gateway, Merchant Account (MID), BIN/IIN

---

## Prerequisites

- **Docker** & **Docker Compose**
- **Git**
- (Optional) [PHPStorm] or your IDE of choice

---

## Getting Started

1. **Clone the repository**
   ```bash
   git clone https://github.com/robencom/metricalo-test.git
   cd metricalo-test
   ```

2. **Environment variables**
   - Copy the sample env files:
     ```bash
     cp .env.example .env
     ```
   - Open `.env` and fill in the APIs credentials:
     ```dotenv
     # Shift4
     SHIFT4_CHARGES_URL=https://api.shift4.com/charges
     SHIFT4_AUTH_KEY=sk_test_...
     SHIFT4_MID=ma_...

     # ACI
     ACI_PAYMENTS_URL=https://eu-test.oppwa.com/v1/payments
     ACI_AUTH_KEY=...
     ACI_ENTITY_ID=...
     ACI_PAYMENT_BRAND=VISA
     ```

3. **Build & start containers**
   ```bash
   docker-compose up --build -d
   ```

4. **Install PHP dependencies inside the `app` container**
   ```bash
   docker-compose exec app composer install --no-interaction --optimize-autoloader
   ```

5. **Run database migrations** (if any)
   > Not applicable—no database required for this assignment

6. **Access the API**
   - API server → http://localhost:8000
   - Example endpoint Shift4:
     ```bash
     curl -X POST http://localhost:8000/api/payment/shift4 \
       -H 'Content-Type: application/json' \
       -d '{"amount":10.00,"currency":"EUR","cardNumber":"4111111111111111","cardExpMonth":12,"cardExpYear":2027,"cardCvv":"123"}'
     ```
   - Example endpoint ACI:
     ```bash
     curl -X POST http://localhost:8000/api/payment/aci \
      -H 'Content-Type: application/json' \
      -d '{"amount":10.00,"currency":"EUR","cardNumber":"4111111111111111","cardExpMonth":12,"cardExpYear":2027,"cardCvv":"123","cardHolderName":"Jane Jones"}'
     ```

7. **Use the CLI**
   - Example command Shift4:
      ```bash
      docker-compose exec app php bin/console app:process-payment shift4 1000 EUR 4111111111111111 12 2027 123
      ```
   - Example command ACI:
      ```bash
      docker-compose exec app php bin/console app:process-payment aci 1000 EUR 4111111111111111 12 2027 123 --cardHolderName="Jane Jones"
      ```
---

## Running Tests

- **Unit, Functional tests**:
  ```bash
  docker-compose exec app php bin/phpunit
  ```

---

## API Documentation (Swagger UI + OpenAPI JSON)
- **Generate or refresh the spec**:
  ```bash
  composer openapi:generate
  ```
This command scans `src/` for `#[OA\…]` attributes and writes public/openapi.json.

- **View the docs**:
    - Interactive HTML (Swagger UI): http://localhost:8000/docs/
    - Raw OpenAPI 3 JSON: http://localhost:8000/openapi.json

- **Keep the spec current**:
    - Run `composer openapi:generate` whenever you change controllers or DTOs.
    - For CI pipelines, run the same script after tests to ensure openapi.json stays in sync.

## Project Structure

```
/ (root)
├─ docker-compose.yml
├─ Dockerfile
├─ README.md
├─ .gitignore
└─ app/ # Symfony application
   ├─ .env.example
   ├─ config/
   ├─ src/
   ├─ tests/
   ├─ bin/console
   └─ ...
```

---