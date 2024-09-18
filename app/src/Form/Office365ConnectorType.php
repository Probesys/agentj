<?php

namespace App\Form;

use App\Entity\Office365Connector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Office365ConnectorType extends ConnectorType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('tenant', null, [
                'label' => 'Entities.Office365Connector.fields.tenant',
            ])
            ->add('client', null, [
                'label' => 'Entities.Office365Connector.fields.client',
            ])
            ->add('clientSecret', null, [
                'label' => 'Entities.Office365Connector.fields.clientSecret',
            ])
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
