<?php

namespace App\Form;

use App\Entity\Domain;
use App\Service\LocaleService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class DomainCustomisationGeneralType extends AbstractType
{
    public function __construct(
        private LocaleService $localeService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('defaultLang', ChoiceType::class, [
            'choices' => array_flip($this->localeService::SUPPORTED_LOCALES),
            'placeholder' => '',
            'label' => new TranslatableMessage('Entities.Domain.fields.defaultLang'),
            'required' => false,
        ]);

        $builder->add('logoFile', FileType::class, [
            'label' => new TranslatableMessage('Entities.Domain.fields.logo'),
            'mapped' => false,
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Domain::class,
        ]);
    }
}
