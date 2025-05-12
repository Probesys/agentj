<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Range;

class DomainType extends AbstractType
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
        $actions = $options['actions'];
        $isEdit = $options['is_edit'];

        $builder
            ->add('domain', null, [
                'label' => 'Entities.Domain.fields.domain',
                'disabled' => $isEdit, // Make the field read-only if in edit mode
            ])
            ->add('srvSmtp', null, [
                'label' => 'Entities.Domain.fields.srvSmtp',
                'required' => true,
            ])
            ->add('smtpPort', NumberType::class, [
                'label' => 'Entities.Domain.fields.smtp_port',
                'constraints' => [
                    new Type(['type' => 'numeric']),
                    new Range([
                        'min' => 1,
                        'max' => 65535
                    ])
                ],
            ])
            ->add('active', null, [
                'label' => 'Entities.Domain.fields.active',
                'required' => false,
            ])
            ->add('rules', ChoiceType::class, [
                'choices' => $actions,
                'mapped' => false,
                'label' => 'Form.PolicyDomain',
                'required' => false,
            ])
            ->add('defaultLang', ChoiceType::class, [
                'choices' => $this->tabLanguages,
                'placeholder' => '',
                'label' => 'Entities.Domain.fields.defaultLang',
                'required' => false,
            ])
            ->add('policy', null, [
                'label' => 'Entities.Domain.fields.policy',
                'required' => true,
                'placeholder' => ''
            ])
            ->add('level', RangeType::class, [
                'label' => 'Entities.Domain.fields.level',
                'attr' => [
                    'min' => $options['minSpamLevel'],
                    'max' => $options['maxSpamLevel'],
                    'step' => 0.1
                ]
            ])
            ->add('mailAuthenticationSender', null, [
                'label' => 'Entities.Domain.fields.mailAuthenticationSender',
                'required' => false,
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo',
                'mapped' => false,
                'required' => false,
            ])
            ->add('domainRelays', CollectionType::class, [
                'entry_type' => DomainRelayType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'required' => false,
            ])
            ->add('quota', CollectionType::class, [
                'entry_type' => QuotaType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'required' => false,
            ])
            ->add('sendUserAlerts', CheckboxType::class, [
                'label' => 'Entities.Domain.fields.sendUserAlerts',
                'required' => false,
            ])
            ->add('sendUserMailAlerts', CheckboxType::class, [
                'label' => 'Entities.Domain.fields.sendUserMailAlerts',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Domain::class,
            'minSpamLevel' => null,
            'maxSpamLevel' => null,
            'actions' => null,
            'is_edit' => false,
        ]);

        $resolver->setDefined('is_edit');
    }
}
