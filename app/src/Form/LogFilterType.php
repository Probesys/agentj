<?php

namespace App\Form;

use App\Entity\Domain;
use App\Model\LogAction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
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
 *     action?: ?LogAction,
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
            ->add('action', EnumType::class, [
                'required' => false,
                'label' => new TranslatableMessage('Entities.Log.fields.action'),
                'class' => LogAction::class,
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

        $resolver->setRequired(['domains']);
        $resolver->setAllowedTypes('domains', Domain::class . '[]');
    }
}
