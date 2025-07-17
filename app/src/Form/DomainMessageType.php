<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DomainMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mailmessage', null, [
                'label' => 'Entities.Domain.fields.mailMessage'
            ])
            ->add('message', null, [
                'label' => 'Entities.Domain.fields.captachaMessage'
            ])
            ->add('confirmCaptchaMessage', null, [
                'label' => 'Entities.Domain.fields.successCaptchaMessage'
            ])
            ->add('messageAlert', null, [
                'label' => 'Entities.Domain.fields.messageAlert'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Domain::class,
        ]);
    }
}
