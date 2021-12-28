<?php

namespace App\Form;

use App\Entity\Domain;
use App\Entity\Policy;
use App\Repository\PolicyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DomainType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $actions = $options['actions'];

        $builder
            ->add('domain', null, [
                'label' => 'Entities.Domain.fields.domain'
            ])
            ->add('srvSmtp', null, [
                'label' => 'Entities.Domain.fields.srvSmtp'
            ])
            ->add('srvImap', null, [
                'label' => 'Entities.Domain.fields.srvImap'
            ])
            ->add('imap_port', null, [
                'empty_data' => '143',
                'label' => 'Entities.Domain.fields.imap_port'
            ])
            ->add('imap_flag', ChoiceType::class, [
                'label' => 'Entities.Domain.fields.imap_flag',
                'required' => false,
                'choices' => [
                    'Generics.labels.none' => '',
                    'STARTTLS' => '/imap/tls',
                    'SSL' => '/imap/ssl'
                ]
            ])
            ->add('imapNoValidateCert', null, [
                'label' => 'Entities.Domain.fields.imapNoValidateCert'
            ])
            ->add('active', null, [
                'label' => 'Entities.Domain.fields.active'
            ])
            ->add('rules', ChoiceType::class, [
                'choices' => $actions,
                'mapped' => false,
                'label' => 'Form.PolicyDomain',
            ])
            ->add('policy', null, [
                'label' => 'Entities.Domain.fields.policy',
                'required' => true,
  //                'data' => 5, //Normal policy
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => Domain::class,
        'minSpamLevel' => null,
        'maxSpamLevel' => null,
        'actions' => null,
        ]);
    }
}
