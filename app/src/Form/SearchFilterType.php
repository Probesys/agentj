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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fromAddr', TextType::class, [
                'required' => false,
                'label' => 'Search.Sender'
            ])
            ->add('subject', TextType::class, [
                'required' => false,
                'label' => 'Search.Subject'
            ])
            ->add('mailId', TextType::class, [
                'required' => false,
                'label' => 'Search.MailId'
            ])
            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'Search.Recipient'
            ])
            ->add('startDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Search.StartDate'
            ])
            ->add('endDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Search.EndDate'
            ])
            ->add('bspamLevelMin', NumberType::class, [
                'required' => false,
                'label' => 'Search.BSpamLevelMin'
            ])
            ->add('bspamLevelMax', NumberType::class, [
                'required' => false,
                'label' => 'Search.BSpamLevelMax'
            ])
            ->add('sizeMin', TextType::class, [
                'required' => false,
                'label' => 'Search.SizeMin'
            ])
            ->add('sizeMax', TextType::class, [
                'required' => false,
                'label' => 'Search.SizeMax'
            ])
            ->add('replyTo', ChoiceType::class, [
                'required' => false,
                'label' => 'Search.ReplyTo',
                'choices' => [
                    'Generics.labels.yes' => 'oui',
                    'Generics.labels.no' => 'non',
                ],
            ])
            ->add('host', TextType::class, [
                'required' => false,
                'label' => "Search.Host"
            ])
            ->add('messageType', ChoiceType::class, [
                'choices' => [
                    'Search.Incoming' => 'incoming',
                    'Search.Outgoing' => 'outgoing',
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => 'incoming',
                'label' => 'Search.MessageType',
                'attr' => ['class' => 'switch-toggle'],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Search.Filter']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
