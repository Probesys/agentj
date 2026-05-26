<?php

namespace App\Form;

use App\Entity\ImapConnector;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ImapConnectorType extends ConnectorType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('imapHost')
            ->add('imapPort', IntegerType::class, [
                'label' => new TranslatableMessage('Entities.ImapConnector.fields.imapPort'),
                'attr' => [
                    'min' => ImapConnector::PORT_RANGE[0],
                    'max' => ImapConnector::PORT_RANGE[1],
                ],
            ])
            ->add('imapProtocol', ChoiceType::class, [
                'label' => new TranslatableMessage('Entities.ImapConnector.fields.imapProtocol'),
                'choices' => [
                    'SSL/TLS' => 'ssl',
                    'StartTLS' => 'starttls',
                    'Generics.labels.none' => '',
                ]
            ])
            ->add('imapNoValidateCert', null, [
                'label' => new TranslatableMessage('Entities.ImapConnector.fields.imapNoValidateCert')
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
