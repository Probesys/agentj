<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ActionsFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('actions', ChoiceType::class, [
                'label' => new TranslatableMessage('Generics.labels.actions'),
                'required' => false,
                'choices' => $options['avalaibleActions'],
                'attr' => [
                    'class' => 'form-control form-control-sm'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);

        $resolver->setRequired('avalaibleActions');
    }

    public function getName(): string
    {
        return 'massive-actions-form';
    }

    public function getBlockPrefix(): string
    {
        return $this->getName();
    }
}
