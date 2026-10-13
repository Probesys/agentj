<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<array{
 *     startDate?: ?\DateTimeImmutable,
 *     endDate?: ?\DateTimeImmutable,
 *     actor?: ?string,
 *     action?: ?string,
 *     mailId?: ?string,
 *     domain?: ?Domain,
 * }>
 */
class LogFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => new TranslatableMessage('Search.StartDate'),
            ])
            ->add('endDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => new TranslatableMessage('Search.EndDate'),
            ])
            ->add('actor', TextType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Entities.Log.fields.actor'),
            ])
            ->add('action', ChoiceType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Entities.Log.fields.action'),
                'choices' => array_combine($options['actions'], $options['actions']),
                'choice_translation_domain' => false,
            ])
            ->add('mailId', TextType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Search.MailId'),
            ])
            ->add('domain', EntityType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Entities.Log.fields.domain'),
                'class' => Domain::class,
                'choices' => $options['domains'],
                'choice_label' => 'domain',
            ])
            ->add('submit', SubmitType::class, [
                'label' => new TranslatableMessage('Search.Filter'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);

        $resolver->setRequired(['actions', 'domains']);
        $resolver->setAllowedTypes('actions', 'string[]');
        $resolver->setAllowedTypes('domains', Domain::class . '[]');
    }
}
