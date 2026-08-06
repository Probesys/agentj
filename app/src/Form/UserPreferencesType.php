<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class UserPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('report', CheckboxType::class, [
                'label' => new TranslatableMessage('Entities.User.fields.report'),
                'required' => false,
            ])
            ->add('humanAuthenticationEnabled', CheckboxType::class, [
                'label' => new TranslatableMessage('Entities.User.fields.activateHumanAuthentication'),
                'required' => false,
                'property_path' => 'humanAuthenticationEnabled',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
