<?php

namespace App\Form;

use App\Entity\Office365Connector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class Office365ConnectorType extends ConnectorType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('tenant', TextType::class, [
                'label' => 'Entities.Office365Connector.fields.tenant',
            ])
            ->add('clientId', TextType::class, [
                'label' => 'Entities.Office365Connector.fields.clientId',
            ])
            ->add('clientSecret', PasswordType::class, [
                'label' => 'Entities.Office365Connector.fields.clientSecret',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Office365Connector::class,
        ]);
    }
}
