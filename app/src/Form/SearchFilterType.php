<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

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
            ->add('mailId', TextType::class, [
                'required' => false,
                'label' => 'Mail ID'
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
            ->add('bspamLevelMin', NumberType::class, [
                'required' => false,
                'label' => 'Score Amavis Min',
            ])
            ->add('bspamLevelMax', NumberType::class, [
                'required' => false,
                'label' => 'Score Amavis Max',
            ])
            ->add('size', TextType::class, [
                'required' => false,
                'label' => 'Taille'
            ])
            ->add('replyTo', ChoiceType::class, [
                'required' => false,
                'label' => 'Réponse à un message',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
            ])
            ->add('host', TextType::class, [
                'required' => false,
                'label' => "Domaine de l'epéditeur"
            ])
            ->add('messageType', ChoiceType::class, [
                'choices' => [
                    'REÇU' => 'incoming',
                    'ENVOYÉ' => 'outgoing',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Type de message',
                'attr' => ['class' => 'switch-toggle'],
                'data' => 'incoming',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Filtrer']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // Set the data_class to null as this form does not directly map to an entity
        $resolver->setDefaults([]);
    }
}
