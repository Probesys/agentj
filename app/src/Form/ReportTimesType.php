<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<list<string>|null>
 */
class ReportTimesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            fn (?array $times): string => implode(', ', $times ?? []),
            fn (?string $times): array => trim($times ?? '') === ''
                ? []
                : array_map('trim', explode(',', $times)),
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => new TranslatableMessage('Entities.Report.fields.times'),
            'help' => new TranslatableMessage('Entities.Report.help.times'),
            'attr' => ['placeholder' => '07:00, 15:00'],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
