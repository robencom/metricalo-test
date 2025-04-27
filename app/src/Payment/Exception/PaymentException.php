<?php
namespace App\Payment\Exception;

use RuntimeException;

/**
 * Thrown when an Acquirer (Shift4 or ACI) returns an error.
 */
class PaymentException extends RuntimeException
{
}
