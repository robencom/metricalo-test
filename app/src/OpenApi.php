<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '0.1.0',
    description: 'Simple endpoint that forwards card payments to Shift4 or ACI.',
    title: 'Payment API'
)]
#[OA\Server(url: '/')]
class OpenApi
{
}
