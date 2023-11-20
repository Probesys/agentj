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
            ->add('allowAnonymousBind', null, [
//                'required' => true,
                'label' => 'Entities.LdapConnector.fields.allowAnonymousBind',
            ])
            ->add('ldapBindDn', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapBindDn',
                'attr'=> ['data-ldap-bind' => 'true']
            ])                
            ->add('LdapPassword', PasswordType::class, [
                'required' => is_null($builder->getData()->getId()),
                'label' => 'Entities.LdapConnector.fields.LdapPassword',
                'attr'=> ['data-ldap-bind' => 'true']
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
            ->add('ldapGroupNameField', null, [
                'required' => false,
                'label' => 'Entities.LdapConnector.fields.ldapGroupNameField',
                'attr'=> ['data-ldap-group' => 'true']
            ])                                                   
            ->add('ldapGroupMemberField', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapGroupMemberField',
                'attr'=> ['data-ldap-group' => 'true']
            ])       
            ->add('ldapUserFilter', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapUserFilter',                
            ]) 
            ->add('ldapGroupFilter', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapGroupFilter',
                'attr'=> ['data-ldap-group' => 'true']
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
