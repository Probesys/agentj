<?php

namespace App\Form;

use App\Entity\LdapConnector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LdapConnectorType extends ConnectorType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('ldapHost', null, [
                'label' => 'Entities.LdapConnector.fields.ldapHost',
            ])
            ->add('ldapPort', null, [
                'label' => 'Entities.LdapConnector.fields.ldapPort',
            ])
            ->add('LdapBaseDN', null, [
                'label' => 'Entities.LdapConnector.fields.LdapBaseDN',
            ])
            ->add('ldapRootDn', null, [
                'label' => 'Entities.LdapConnector.fields.ldapRootDn',
            ])                
            ->add('LdapPassword', null, [
                'label' => 'Entities.LdapConnector.fields.LdapPassword',
            ])
            ->add('ldapLoginField', null, [
                'label' => 'Entities.LdapConnector.fields.ldapLoginField',
            ])     
            ->add('ldapRealNameField', null, [
                'label' => 'Entities.LdapConnector.fields.ldapRealNameField',
            ])
            ->add('ldapEmailField', null, [
                'label' => 'Entities.LdapConnector.fields.ldapEmailField',
            ])
            ->add('ldapGroupField', null, [
                'label' => 'Entities.LdapConnector.fields.ldapGroupField',
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
