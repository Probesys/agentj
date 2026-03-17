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
use Symfony\Component\Translation\TranslatableMessage;

class SearchFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fromAddr', TextType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.Sender'),
            ])
            ->add('subject', TextType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.Subject'),
            ])
            ->add('mailId', TextType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.MailId'),
            ])
            ->add('email', TextType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.Recipient'),
            ])
            ->add('startDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => new TranslatableMessage('Search.StartDate'),
            ])
            ->add('endDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => new TranslatableMessage('Search.EndDate'),
            ])
            ->add('bspamLevelMin', NumberType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.BSpamLevelMin'),
            ])
            ->add('bspamLevelMax', NumberType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.BSpamLevelMax'),
            ])
            ->add('sizeMin', TextType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.SizeMin'),
            ])
            ->add('sizeMax', TextType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.SizeMax'),
            ])
            ->add('replyTo', ChoiceType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.ReplyTo'),
                'choices' => [
                    'Generics.labels.yes' => 'oui',
                    'Generics.labels.no' => 'non',
                ],
            ])
            ->add('host', TextType::class, [
                'required' => false,
                'label' => new TranslatableMessage("Search.Host"),
            ])
            ->add('messageType', ChoiceType::class, [
                'choices' => ['incoming', 'outgoing'],
                'choice_label' => function (string $choice): TranslatableMessage {
                    return match ($choice) {
                        'incoming' => new TranslatableMessage("Search.Incoming"),
                        'outgoing' => new TranslatableMessage("Search.Outgoing"),
                    };
                },
                'expanded' => true,
                'multiple' => false,
                'data' => 'incoming',
                'label' => new TranslatableMessage('Search.MessageType'),
                'attr' => ['class' => 'switch-toggle'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => new TranslatableMessage('Search.Filter'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
