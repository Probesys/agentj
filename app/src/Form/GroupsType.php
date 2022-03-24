<?php

namespace App\Form;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\User;
use App\Entity\Policy;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class GroupsType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $actions = $options['actions'];
        $user = $options['user'];

        $builder
            ->add('name', null, [
                'label' => 'Entities.Group.fields.name'
            ])
            ->add('overrideUser', null, [
                'label' => 'Entities.Group.fields.overrideUser'
            ])
            ->add('policy', null, [
                'label' => 'Entities.Group.fields.policy',
                'required' => true,
                //'empty_data' => $policyNormal, //not work
                'placeholder' => 'Select a policy'
            ])
            ->add('wb', ChoiceType::class, [
                'choices' => $actions,
                'label' => 'Form.PolicyDomain',
                'required' => false
                ])
            ->add('domain', EntityType::class, [
                'label' => 'Entities.Group.fields.domain',
                'class' => Domain::class,
                'multiple' => false,
                'attr' => ['class' => 'select2'],
                'placeholder' => 'Select Domain',
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

                    ;
                },
            ])
            ->add('active', null, [
                'label' => 'Generics.fields.active'
            ])


        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => Groups::class,
        'actions' => null,
        'user' => null
        ]);
    }
}
