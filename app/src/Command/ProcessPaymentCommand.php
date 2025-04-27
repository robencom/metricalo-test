<?php
namespace App\Command;

use App\Payment\DTO\PaymentRequest;
use App\Payment\Service\PaymentService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:process-payment',
    description: 'Process a card payment via a specified Acquirer (shift4|aci).'
)]
class ProcessPaymentCommand extends Command
{
    public function __construct(
        private readonly PaymentService $service,
        private readonly ValidatorInterface $validator) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('provider',InputArgument::REQUIRED, 'Payment Gateway: shift4 or aci')
            ->addArgument('amount',InputArgument::REQUIRED, 'Amount in minor units (e.g. cents)')
            ->addArgument('currency',InputArgument::REQUIRED, 'ISO 4217 currency code')
            ->addArgument('cardNumber',InputArgument::REQUIRED, 'Card PAN')
            ->addArgument('expMonth',InputArgument::REQUIRED, 'Expiry month (1–12)')
            ->addArgument('expYear',InputArgument::REQUIRED, 'Expiry year (e.g. 2025)')
            ->addArgument('cvv',InputArgument::REQUIRED, 'Card CVV')
            ->addOption('cardHolderName',null, InputArgument::OPTIONAL, 'Required by ACI');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // hydrate request
        $req = new PaymentRequest();
        $req->amount = (int)$input->getArgument('amount');
        $req->currency = $input->getArgument('currency');
        $req->cardNumber = $input->getArgument('cardNumber');
        $req->cardExpMonth = (int)$input->getArgument('expMonth');
        $req->cardExpYear = (int)$input->getArgument('expYear');
        $req->cardCvv = $input->getArgument('cvv');
        $req->cardHolderName = $input->getOption('cardHolderName');

        // validate arguments
        $provider = strtolower($input->getArgument('provider'));
        $violations = $this->validator->validate($req, null, ['Default', $provider]);
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $output->writeln("<error>{$violation->getPropertyPath()}: {$violation->getMessage()}</error>");
            }
            return Command::FAILURE;
        }

        // process request
        try {
            $resp = $this->service->process($input->getArgument('provider'), $req);
            $output->writeln(json_encode($resp));
            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
