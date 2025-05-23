<?php

namespace App\Form;

use App\Entity\ImapConnector;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImapConnectorType extends ConnectorType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('imapHost')
            ->add('imapPort', IntegerType::class, [
                'label' => 'Entities.ImapConnector.fields.imap_port',
                'attr' => [
                    'min' => ImapConnector::PORT_RANGE[0],
                    'max' => ImapConnector::PORT_RANGE[1],
                ],
            ])
            ->add('imapProtocol', ChoiceType::class,[
                'label' => 'Entities.ImapConnector.fields.imapProtocol',
                'required' => false,
                'choices' => [
                    'Generics.labels.none' => '',
                    'SSL' => 'ssl',
                    'TLS' => 'tls',
                ]
            ])
            ->add('imapNoValidateCert', null, [
                'label' => 'Entities.ImapConnector.fields.imapNoValidateCert'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImapConnector::class,
        ]);
    }
}
