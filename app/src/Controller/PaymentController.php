<?php
namespace App\Controller;

use App\Payment\DTO\PaymentRequest;
use App\Payment\Form\PaymentRequestType;
use App\Payment\Service\PaymentService;
use App\Payment\Exception\PaymentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/payment/{provider}',
    operationId: 'processPayment',
    summary: 'Process a one-off card payment',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/PaymentRequest')
    ),
    tags: ['Payments'],
    parameters: [
        new OA\PathParameter(
            name: 'provider',
            description: 'Gateway to use (`shift4`, `aci`, …)',
            required: true,
            schema: new OA\Schema(
                type: 'string',
                enum: ['shift4', 'aci'],
                example: 'shift4'
            )
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Payment accepted',
            content: new OA\JsonContent(ref: '#/components/schemas/PaymentResponse')
        ),
        new OA\Response(response: 400, description: 'Invalid payload'),
        new OA\Response(response: 502, description: 'Upstream gateway failure')
    ]
)]
#[Route('/api/payment/{provider}', methods: ['POST'])]
class PaymentController extends AbstractController
{
    public function __invoke(
        string $provider,
        Request $request,
        ValidatorInterface $validator,
        PaymentService $service
    ): JsonResponse {
        // 1) Normalize payload
        $data = $request->toArray();

        // 2) Build & submit form (auto-maps into PaymentRequest)
        $form = $this->createForm(PaymentRequestType::class, null, [
            'provider' => strtolower($provider),
        ]);
        $form->submit($data);

        // 3) Handle validation errors
        if (! $form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $err) {
                $field = $err->getOrigin()->getName();
                $errors[] = sprintf('%s: %s', $field, $err->getMessage());
            }
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // 4) Process payment
        /** @var PaymentRequest $dto */
        $dto = $form->getData();
        try {
            $resp = $service->process($provider, $dto);
            return $this->json($resp);
        } catch (PaymentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }
}
