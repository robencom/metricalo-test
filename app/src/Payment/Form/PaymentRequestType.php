<?php

namespace App\Payment\Form;

use App\Payment\DTO\PaymentRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    IntegerType, TextType, MoneyType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount',MoneyType::class)
            ->add('currency',TextType::class)
            ->add('cardNumber',TextType::class)
            ->add('cardExpMonth',IntegerType::class)
            ->add('cardExpYear',IntegerType::class)
            ->add('cardCvv',TextType::class)
            ->add('cardHolderName',TextType::class, ['required'=> false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaymentRequest::class,
            'csrf_protection' => true,
            'validation_groups' => function (FormInterface $form) {
                return ['Default', $form->getConfig()->getOption('provider')];
            },
        ]);

        $resolver->setRequired('provider');
        $resolver->setAllowedTypes('provider','string');
    }
}