<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fromAddr', TextType::class, [
                'required' => false,
                'label' => 'Expéditeur'
            ])
            ->add('subject', TextType::class, [
                'required' => false,
                'label' => 'Objet'
            ])
            ->add('messageId', TextType::class, [
                'required' => false,
                'label' => 'Message ID'
            ])
            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'Déstinataire'
            ])
            ->add('startDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date de début'
            ])
            ->add('endDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date de fin'
            ])
            ->add('messageType', ChoiceType::class, [
                'choices'  => [
                    'Reçu' => 'incoming',
                    'Envoyé' => 'outgoing',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Type de message',
                'attr' => ['class' => 'switch-toggle'],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Filtrer']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // Set the data_class to null as this form does not directly map to an entity
        $resolver->setDefaults([]);
    }
}
