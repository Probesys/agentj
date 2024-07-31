<?php

namespace App\Form;

use App\Entity\ImapConnector;
use App\Model\ImapPorts;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImapConnectorType extends ConnectorType
{
    public function __construct(private ImapPorts $imapPorts) {

    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder            
            ->add('imapHost')
            ->add('imapPort',ChoiceType::class,[
                'label' => 'Entities.ImapConnector.fields.imap_port',
                'placeholder' => '',
                'choices' => $this->imapPorts::allValues(),
                'multiple' => false,
                'expanded' => false,

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
