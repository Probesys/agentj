<?php

namespace App\Form;

use App\Entity\Groups;
use App\Entity\User;
use App\Repository\GroupsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class UserType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $labelEmail = 'Entities.User.fields.email';
        if ($options['alias']) {
            $labelEmail = 'Entities.User.fields.alias';
        }
        $allowedomainIds = $options['allowedomainIds'];
        $builder
                ->add('email', EmailType::class, [
                    'label' => $labelEmail,
                    'required' => true,
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
                    'multiple' => true,
                    'attr' => ['class' => 'select2'],
                    'placeholder' => ' ',
                    'required' => false,
                    'choice_label' => function ($group, $key, $index) {
                        return $group->getName() . ' (' . $group->getDomain() . ')';
                    },
                    'query_builder' => function (GroupsRepository $rep) use ($allowedomainIds) {
                        $retRepo = $rep->createQueryBuilder('g')
                                ->leftJoin('g.domain', 'd');
                        if ($allowedomainIds) {
                            $retRepo->where('d.id in (' . implode(',', $allowedomainIds) . ')');
                        }
                        return $retRepo;
                    },
                ])
                ->add('domains', null, [
                    'attr' => ['class' => 'select2'],
                    'label' => 'Entities.User.fields.domain',
                ])
                ->add('report', null, [
                    'label' => 'Entities.User.fields.report',
                ])
                ->add('originalUser', Select2EntityType::class, [
                    'multiple' => false,
                    'placeholder' => '',
                    'label' => 'Entities.User.fields.originalUser',
                    'class' => 'App\Entity\User',
                    'primary_key' => 'id',
                    'remote_route' => 'autocomplete_user',
                    'required' => true,
                ])
                ->add('sharedWith', Select2EntityType::class, [
                    'multiple' => true,
                    'placeholder' => '',
                    'label' => 'Entities.User.fields.sharedBy',
                    'class' => 'App\Entity\User',
                    'primary_key' => 'id',
                    'remote_route' => 'autocomplete_user',
                    'required' => false,
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'alias' => false,
            'allowedomainIds' => []
        ]);
    }
}
