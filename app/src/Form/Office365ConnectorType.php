<?php

namespace App\Form;

use App\Entity\Office365Connector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Office365ConnectorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tenant')
            ->add('client')
            ->add('clientSecret')
//            ->add('connector')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Office365Connector::class,
        ]);
    }
}
