<?php

namespace App\Form;

use App\Entity\Policy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Translation\TranslatableMessage;

class PolicyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choicesYesNo = ['Generics.labels.no' => "N", 'Generics.labels.yes' => "Y"];
        $builder
            ->add('policyName', null, [
                'label' => 'Generics.fields.name',
            ])
            ->add('virusLover', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.virusLover'),
            ])
            ->add('spamLover', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamLover'),
            ])
            ->add('bannedFilesLover', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.bannedFilesLover'),
            ])
            ->add('badHeaderLover', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.badHeaderLover'),
            ])
            ->add('bypassVirusChecks', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.bypassVirusChecks'),
            ])
            ->add('bypassSpamChecks', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.bypassSpamChecks'),
            ])
            ->add('bypassBannedChecks', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.bypassBannedChecks'),
            ])
            ->add('bypassHeaderChecks', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.bypassHeaderChecks'),
            ])

            ->add('virusQuarantineTo', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.virusQuarantineTo'),
            ])
            ->add('spamQuarantineTo', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamQuarantineTo'),
            ])
            ->add('bannedQuarantineTo', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.bannedQuarantineTo'),
            ])
            ->add('badHeaderQuarantineTo', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.badHeaderQuarantineTo'),
            ])
            ->add('uncheckedQuarantineTo', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.uncheckedQuarantineTo'),
            ])
            ->add('cleanQuarantineTo', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.cleanQuarantineTo'),
            ])
            ->add('archiveQuarantineTo', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.archiveQuarantineTo'),
            ])

            ->add('spamTagLevel', NumberType::class, [
                'required' => false,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamTagLevel'),
            ])
            ->add('spamTag2Level', NumberType::class, [
                'required' => false,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamTag2Level'),
            ])
            ->add('spamTag3Level', NumberType::class, [
                'required' => false,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamTag3Level'),
            ])
            ->add('spamKillLevel', NumberType::class, [
                'required' => false,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamKillLevel'),
            ])
            ->add('spamDsnCutoffLevel', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamDsnCutoffLevel'),
            ])
            ->add('spamQuarantineCutoffLevel', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamQuarantineCutoffLevel'),
            ])
            ->add('spamSubjectTag', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamSubjectTag'),
            ])
            ->add('spamSubjectTag2', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamSubjectTag2'),
            ])
            ->add('spamSubjectTag3', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamSubjectTag3'),
            ])

            ->add('addrExtensionVirus', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.addrExtensionVirus'),
            ])
            ->add('addrExtensionSpam', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.addrExtensionSpam'),
            ])
            ->add('addrExtensionBanned', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.addrExtensionBanned'),
            ])
            ->add('addrExtensionBadHeader', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.addrExtensionBadHeader'),
            ])
            ->add('warnvirusrecip', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => false,
                'label' => new TranslatableMessage('Entities.Policy.fields.warnvirusrecip'),
            ])
            ->add('warnbannedrecip', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => false,
                'label' => new TranslatableMessage('Entities.Policy.fields.warnbannedrecip'),
            ])
            ->add('warnbadhrecip', ChoiceType::class, [
                'choices' =>  $choicesYesNo,
                'mapped' => false,
                'label' => new TranslatableMessage('Entities.Policy.fields.warnbadhrecip'),
            ])
            ->add('newvirusAdmin', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.newvirusAdmin'),
            ])
            ->add('virusAdmin', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.virusAdmin'),
            ])
            ->add('bannedAdmin', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.bannedAdmin'),
            ])
            ->add('badHeaderAdmin', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.badHeaderAdmin'),
            ])
            ->add('spamAdmin', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.spamAdmin'),
            ])
            ->add('messageSizeLimit', NumberType::class, [
                'required' => false,
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.messageSizeLimit'),
                'attr' => ['input_addon' => new TranslatableMessage('Generics.units.bytes')],
            ])
            ->add('bannedRulenames', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.bannedRulenames'),
            ])
            ->add('disclaimerOptions', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.disclaimerOptions'),
            ])
            ->add('forwardMethod', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.forwardMethod'),
            ])
            ->add('saUserconf', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.saUserconf'),
            ])
            ->add('saUsername', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.saUsername'),
            ])
            ->add('uncheckedLover', null, [
                'mapped' => true,
                'label' => new TranslatableMessage('Entities.Policy.fields.uncheckedLover'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Policy::class,
        ]);
    }
}
