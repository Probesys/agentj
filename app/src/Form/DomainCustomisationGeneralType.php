<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DomainCustomisationGeneralType extends AbstractType
{
    /**
     * @var array<string, string>
     */
    private array $tabLanguages;

    public function __construct(ParameterBagInterface $params)
    {
        $langs = explode('|', $params->get('app_locales'));
        foreach ($langs as $lang) {
            $this->tabLanguages[$lang] = $lang;
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('defaultLang', ChoiceType::class, [
            'choices' => $this->tabLanguages,
            'label' => 'Entities.Domain.fields.defaultLang',
            'required' => false,
        ]);

        $builder->add('logoFile', FileType::class, [
            'label' => 'Logo',
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
