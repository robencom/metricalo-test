<?php
namespace App\Payment\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represents the cardholder's payment instruction.
 */
class PaymentRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('numeric')]
    #[Assert\GreaterThan(0)]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'The amount must be a positive number, with up to two decimal places.'
    )]
    public float $amount;

    #[Assert\NotBlank]
    #[Assert\Currency]
    #[Assert\Regex(
        pattern: '/^[A-Z]{3}$/',
        message: 'Currency must be a valid 3-letter uppercase ISO-4217 code.'
    )]
    public string $currency;

    #[Assert\NotBlank]
    #[Assert\CardScheme(['VISA','MASTERCARD','DISCOVER','AMEX'])]
    public string $cardNumber;

    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 12)]
    public int $cardExpMonth;

    #[Assert\NotBlank]
    public int $cardExpYear;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 4)]
    public string $cardCvv;

    #[Assert\NotBlank(groups: ['aci'])]
    public ?string $cardHolderName = null;

    #[Assert\Callback]
    public function validateExpiration(ExecutionContextInterface $ctx): void
    {
        $now = new \DateTimeImmutable();
        $year = $this->cardExpYear;
        $month = $this->cardExpMonth;
        $currentY = (int)$now->format('Y');
        $currentM = (int)$now->format('n');
        $maxY = $currentY + 10;

        // Year out of range?
        if ($year < $currentY) {
            $ctx->buildViolation('Expiration year {{ year }} is in the past.')
                ->setParameter('{{ year }}', (string)$year)
                ->atPath('cardExpYear')
                ->addViolation();
            return;
        }
        if ($year > $maxY) {
            $ctx->buildViolation('Expiration year {{ year }} is too far in the future (max {{ max }}).')
                ->setParameter('{{ year }}', (string)$year)
                ->setParameter('{{ max }}', (string)$maxY)
                ->atPath('cardExpYear')
                ->addViolation();
            return;
        }

        // If year is this year, the month must not be earlier than now
        if ($year === $currentY && $month < $currentM) {
            $ctx->buildViolation('Expiration month {{ month }} has already passed for {{ year }}.')
                ->setParameter('{{ month }}', sprintf('%02d', $month))
                ->setParameter('{{ year }}', (string)$year)
                ->atPath('cardExpMonth')
                ->addViolation();
        }
    }
}
