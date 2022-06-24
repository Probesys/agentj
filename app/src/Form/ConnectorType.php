<?php

namespace App\Form;

use App\Entity\Connector;
use App\Model\ConnectorTypes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
class ConnectorType extends AbstractType
{
    private $connectorType;
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->connectorType = $options['connectorType'];
        $builder
            ->add('name')
            ->add('active')
                ->add('office365Connector', Office365ConnectorType::class, [
                'label' => false,
            ])
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                [$this, 'onPreSetData']
            )                
        ;
    }

    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($this->connectorType == ConnectorTypes::LDAP){
            $form->remove('office365Connector');
        }
    }
    
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Connector::class,
            'connectorType' => ConnectorTypes::Office365,
//            'allow_extra_fields' => true
        ]);
    }
}
