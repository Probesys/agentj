<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatableMessage;

class GroupsWblistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class)
            ->add('wbRule', ChoiceType::class, [
                'choices' => ['accept', 'block', 'allow'],
                'choice_label' => function (string $choice): TranslatableMessage {
                    return new TranslatableMessage("Entities.WBList.rules.{$choice}");
                },
                'label' => 'Entities.WBList.fields.wbRule',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
