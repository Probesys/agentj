<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class DomainCustomisationHumanAuthenticationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('mailmessage', TextareaType::class, [
            'label' => new TranslatableMessage('Entities.Domain.fields.mailmessage'),
            'attr' => [
                'data-controller' => 'ckeditor',
            ],
        ]);

        $builder->add('message', TextareaType::class, [
            'label' => new TranslatableMessage('Entities.Domain.fields.message'),
            'attr' => [
                'data-controller' => 'ckeditor',
            ],
        ]);

        $builder->add('confirmCaptchaMessage', TextareaType::class, [
            'label' => new TranslatableMessage('Entities.Domain.fields.confirmCaptchaMessage'),
            'attr' => [
                'data-controller' => 'ckeditor',
            ],
        ]);

        $builder->add('humanAuthenticationFooter', TextareaType::class, [
            'required' => false,
            'empty_data' => '',
            'label' => new TranslatableMessage('Entities.Domain.fields.humanAuthenticationFooter'),
            'attr' => [
                'data-controller' => 'ckeditor',
            ],
        ]);

        $builder->add('humanAuthenticationStylesheet', TextareaType::class, [
            'required' => false,
            'empty_data' => '',
            'label' => new TranslatableMessage('Entities.Domain.fields.humanAuthenticationStylesheet'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Domain::class,
        ]);
    }
}
