<?php
namespace App\Controller;

use App\Payment\DTO\PaymentRequest;
use App\Payment\Form\PaymentRequestType;
use App\Payment\Service\PaymentService;
use App\Payment\Exception\PaymentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
