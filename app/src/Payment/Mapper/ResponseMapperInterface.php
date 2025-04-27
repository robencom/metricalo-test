<?php
namespace App\Payment\Mapper;

use App\Payment\DTO\PaymentResponse;

interface ResponseMapperInterface
{
    public function map(array $data): PaymentResponse;
}
