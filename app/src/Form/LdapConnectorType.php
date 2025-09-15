<?php

namespace App\Form;

use App\Entity\LdapConnector;
use App\Entity\Groups;
use App\Repository\GroupsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LdapConnectorType extends ConnectorType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $domain = $builder->getData()->getDomain();

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
                'label' => 'Entities.LdapConnector.fields.allowAnonymousBind',
            ])
            ->add('ldapBindDn', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapBindDn',
                'attr' => ['data-ldap-bind' => 'true']
            ])
            ->add('LdapPassword', PasswordType::class, [
                'required' => is_null($builder->getData()->getId()),
                'label' => 'Entities.LdapConnector.fields.LdapPassword',
                'attr' => ['data-ldap-bind' => 'true']
            ])
            ->add('targetGroups', EntityType::class, [
                'attr' => ['class' => 'select2'],
                'required' => false,
                'class' => Groups::class,
                'multiple' => true,
                'expanded' => false,
                'query_builder' => function (GroupsRepository $groupsRepository) use ($domain) {
                    return $groupsRepository->createQueryBuilder('g')
                        ->where('g.domain = :domain')
                        ->setParameter('domain', $domain);
                },
                'label' => 'Entities.Connector.fields.targetGroups',
            ])
            ->add('ldapRealNameField', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapRealNameField',
            ])
            ->add('ldapEmailField', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapEmailField',
            ])
            ->add('ldapAliasField', null, [
                'required' => false,
                'label' => 'Entities.LdapConnector.fields.ldapAliasField',
            ])
            ->add('ldapSharedWithField', null, [
                'required' => false,
                'label' => 'Entities.LdapConnector.fields.ldapSharedWithField',
            ])
            ->add('ldapGroupNameField', null, [
                'required' => false,
                'label' => 'Entities.LdapConnector.fields.ldapGroupNameField',
                'attr' => ['data-ldap-group' => 'true']
            ])
            ->add('ldapGroupMemberField', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapGroupMemberField',
                'attr' => ['data-ldap-group' => 'true']
            ])
            ->add('ldapUserFilter', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapUserFilter',
            ])
            ->add('ldapGroupFilter', null, [
                'required' => true,
                'label' => 'Entities.LdapConnector.fields.ldapGroupFilter',
                'attr' => ['data-ldap-group' => 'true']
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
