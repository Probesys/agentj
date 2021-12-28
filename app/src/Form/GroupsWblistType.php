<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class GroupsWblistType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $actions = $options['actions'];
        $builder->add('email', TextType::class)
        ->add('wb', ChoiceType::class, [
        'choices' => $actions,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => null,
        'actions' => null,
        ]);
    }
}
