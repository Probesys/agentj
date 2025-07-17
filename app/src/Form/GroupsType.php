<?php

namespace App\Form;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Repository\DomainRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class GroupsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $actions = $options['actions'];
        $user = $options['user'];

        $builder
            ->add('id', HiddenType::class, [
                'attr' => ['data-form-group-target' =>  'group'],
                'mapped' => false,
            ])
            ->add('name', null, [
                'label' => 'Entities.Group.fields.name'
            ])
            ->add('overrideUser', null, [
                'label' => 'Entities.Group.fields.overrideUser'
            ])
            ->add('policy', null, [
                'label' => 'Entities.Group.fields.policy',
                'required' => true,
                'placeholder' => 'Generics.actions.choosePolicy'
            ])
            ->add('wb', ChoiceType::class, [
                'choices' => $actions,
                'label' => 'Form.PolicyDomain',
                'required' => false
            ])
            ->add('priority', NumberType::class, [
                'label' => 'Generics.fields.priority',
                'required' => true,
                'attr' => [
                    'data-action' => 'blur->form-group#checkPriorityValidity',
                    'data-form-group-target' => 'priority',
                ]
            ])
            ->add('domain', EntityType::class, [
                'label' => 'Entities.Group.fields.domain',
                'class' => Domain::class,
                'multiple' => false,
                'attr' => [
                    'data-form-group-target' => 'domain',
                    'data-action' => 'change->form-group#checkPriorityValidity',
                ],
                'placeholder' => 'Generics.actions.chooseDomain',
                'required' => true,
                'query_builder' => function (DomainRepository $rep) use ($user) {
                    $builder = $rep->createQueryBuilder('d')
                        ->leftJoin('d.users', 'u')
                        ->where('d.active = 1 ')
                        ->orderBy('d.domain', 'asc');

                    if ($user) {
                        $builder->where('u.id =' . $user->getId());
                    }

                    return $builder;
                },
            ])
            ->add('active', null, [
                'label' => 'Generics.fields.active'
            ])
            ->add('quota', CollectionType::class, [
                'entry_type' => QuotaType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])


        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Groups::class,
            'actions' => null,
            'user' => null,
        ]);
    }
}
