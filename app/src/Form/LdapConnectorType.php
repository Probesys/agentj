<?php

namespace App\Form;

use App\Entity\LdapConnector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LdapConnectorType extends ConnectorType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('ldapHost', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapHost',
            ])
            ->add('ldapPort', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapPort',
                'attr' => ['pattern' => '[0-9]+']
            ])
            ->add('LdapBaseDN', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.LdapBaseDN',
            ])
            ->add('ldapBindDn', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapBindDn',
            ])                
            ->add('LdapPassword', PasswordType::class, [
                'required' => is_null($builder->getData()->getId()),
                'label' => 'Entities.LdapConnector.fields.LdapPassword',
            ])
            ->add('ldapLoginField', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapLoginField',
            ])     
            ->add('ldapRealNameField', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapRealNameField',
            ])
            ->add('ldapEmailField', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapEmailField',
            ])
            ->add('synchronizeGroup', null, [
                'required' => false,
                'label' => 'Entities.LdapConnector.fields.synchronizeGroup',
            ])                    
            ->add('ldapGroupField', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapGroupField',
            ])   
            ->add('ldapGroupMemberField', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapGroupMemberField',
            ])       
            ->add('ldapUserFilter', null, [
                'required' => false,
                'label' => 'Entities.LdapConnector.fields.ldapUserFilter',
            ]) 
            ->add('ldapGroupFilter', null, [
                'required' => false,
                'label' => 'Entities.LdapConnector.fields.ldapGroupFilter',
            ])                 
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LdapConnector::class,
        ]);
    }
}
