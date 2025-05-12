<?php

namespace App\Form;

use App\Entity\Groups;
use App\Entity\User;
use App\Entity\Domain;
use App\Form\UserAutocompleteField;
use App\Repository\GroupsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class UserType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $labelEmail = 'Entities.User.fields.email';
        if ($options['alias']) {
            $labelEmail = 'Entities.User.fields.alias';
        }
        $allowedDomains = $options['allowedDomains'];


        $domainHasIMAPConnector = $options['domainHasIMAPConnector'] ?? false;

        $adminForm = $options['adminForm'] ?? false;

        $builder
            ->add('email', EmailType::class, [
                'label' => $labelEmail,
                'required' => !$adminForm,
            ])
            ->add('emailRecovery', EmailType::class, [
                'label' => 'Entities.User.fields.emailRecovery',
                'required' => false,
            ])
            ->add('fullname', null, [
                'label' => 'Entities.User.fields.fullName',
            ])
            ->add('username', null, [
                'label' => 'Entities.User.fields.userName',
                'required' => true,
                'trim' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Entities.User.fields.roles',
                'attr' => ['class' => 'select2'],
                'choices' => ['Local Admin' => '["ROLE_ADMIN"]', 'Super Admin' => '["ROLE_SUPER_ADMIN"]'],
                'mapped' => false
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'label' => 'Entities.User.fields.password',
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'first_options' => ['label' => 'Entities.User.fields.password'],
                'second_options' => ['label' => 'Entities.User.fields.repeatPassword'],
                'mapped' => false,
            ])
            ->add('groups', EntityType::class, [
                'label' => 'Entities.User.fields.groups',
                'class' => Groups::class,
                'multiple' => true,           // This allows multiple checkboxes to be checked
                'expanded' => true,           // This makes it render as checkboxes
                'required' => false,
                'choice_label' => function ($group, $key, $index) {
                    return $group->getName() . ' (' . $group->getDomain() . ')';
                },
                'query_builder' => function (GroupsRepository $rep) use ($allowedDomains) {
                    $retRepo = $rep->createQueryBuilder('g')
                        ->leftJoin('g.domain', 'd');
                    if ($allowedDomains) {
                        $retRepo->where('d.id in (:allowedDomains)')
                            ->setParameter('allowedDomains', $allowedDomains);
                    }
                    return $retRepo;
                },
                'attr' => ['style' => 'height:auto;']
            ])
            ->add('domain', null, [
                'label' => 'Entities.User.fields.domain',
                'attr' => [
                    'class' => 'select2',
                    'onChange' => 'toggleImapLogin(this)'  // JavaScript function for handling changes
                ]
            ])
            ->add('report', null, [
                'label' => 'Entities.User.fields.report',
            ])
            ->add('originalUser', UserAutocompleteField::class, [
                'multiple' => false,
                'required' => true,
                'label' => 'Entities.User.fields.originalUser',
            ])
            ->add('sharedWith', UserAutocompleteField::class, [
                'multiple' => true,
                'required' => false,
                'label' => 'Entities.User.fields.sharedWith',
            ]);


        if ($adminForm) {
            $builder->add('domains', EntityType::class, [
                'class' => Domain::class,
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'label' => 'Entities.User.fields.domain',
                'attr' => ['style' => 'height:auto;']
            ]);
        }

        $builder->add('imapLogin', null, [
            'label' => 'IMAP Login',
            'required' => false,
            'attr' => [
                'class' => 'imap-login-field',
            ]
        ]);
   



        if ($options['include_quota']) {
            $builder->add('quota', CollectionType::class, [
                'entry_type' => QuotaType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'alias' => false,
            'allowedDomains' => [],
            'include_quota' => true,
            'adminForm' => false,
            'domainHasIMAPConnector' => false
        ]);
    }
}
