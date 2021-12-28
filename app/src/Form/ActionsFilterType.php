<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionsFilterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('actions', ChoiceType::class, [
                'label' => 'Actions',
                'required' => false,
                'choices' => $options['avalaibleActions'],
                'attr' => [
                    'class' => 'form-control form-control-sm'
                ],
            ])
            ->add('per_page', ChoiceType::class, [
                'label' => 'Generics.labels.item_per_page',
                'attr' => [
                    'class' => 'form-control form-control-sm'
                ],
                'required' => false,
                'choices' => [10 => 10, 25 => 25, 50 => 50 , 100 => 100, 200 => 200, 500 => 500 ,1000 => 1000,3000 => 3000],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => null,
        ]);

        $resolver->setRequired('avalaibleActions');
    }

  /**
   * @return string
   */
    public function getName()
    {
        return 'massive-actions-form';
    }

    public function getBlockPrefix()
    {
        return $this->getName();
    }
}
