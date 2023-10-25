<?php

namespace App\Form;

use App\Entity\Connector;
use App\Model\ConnectorTypes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
class ConnectorType extends AbstractType
{

    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Entities.Connector.fields.name'
            ])
            ->add('type', HiddenType::class) 
            ->add('synchronizeGroup', null, [
                'required' => false,
                'label' => 'Entities.LdapConnector.fields.synchronizeGroup',
            ])                 
        ;
    }


    
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Connector::class,
        ]);
    }
}
