<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;

class HumanAuthenticationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => new TranslatableMessage('Message.HumanAuthentication.toVerify.email'),
        ]);

        $builder->add('emailEmpty', EmailType::class, [
            'label' => false,
            'required' => false,
            'attr' => [
                'style' => 'display:none',
            ],
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => new TranslatableMessage('Message.HumanAuthentication.toVerify.submit'),
            'attr' => [
                'class' => 'button--primary button--block',
            ],
        ]);
    }
}
