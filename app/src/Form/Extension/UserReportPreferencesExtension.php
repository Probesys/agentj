<?php

namespace App\Form\Extension;

use App\Entity\User;
use App\Form\UserPreferencesType;
use App\Form\UserType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatableMessage;

class UserReportPreferencesExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [UserType::class, UserPreferencesType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->initialize(...));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->preserveAbsentChoice(...));
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->applyChoice(...));
    }

    private function initialize(FormEvent $event): void
    {
        $user = $event->getData();
        if (!$user instanceof User) {
            return;
        }

        $form = $event->getForm();
        $form->add('reportSkipWeekends', CheckboxType::class, [
            'mapped' => false,
            'required' => false,
            'data' => $user->getEffectiveReportSkipWeekends(),
            'false_values' => [null, false, '0'],
            'label' => new TranslatableMessage('Entities.Report.fields.skipWeekends'),
            'help' => new TranslatableMessage('Entities.Report.help.userSkipWeekends'),
        ]);
        $form->add('reportSkipWeekendsMode', ChoiceType::class, [
            'mapped' => false,
            'label' => false,
            'choices' => [
                'Entities.Report.labels.domainWeekends' => 'inherit',
                'Entities.Report.labels.personalWeekends' => 'custom',
            ],
            'data' => $user->getReportSkipWeekends() === null ? 'inherit' : 'custom',
            'empty_data' => $user->getReportSkipWeekends() === null ? 'inherit' : 'custom',
        ]);
    }

    private function preserveAbsentChoice(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (
            is_array($data)
            && $form->has('reportSkipWeekends')
            && !array_key_exists('reportSkipWeekendsMode', $data)
        ) {
            if (array_key_exists('reportSkipWeekends', $data)) {
                $data['reportSkipWeekendsMode'] = 'custom';
            } else {
                $data['reportSkipWeekends'] = $form->get('reportSkipWeekends')->getData() ? '1' : null;
            }
            $event->setData($data);
        }
    }

    private function applyChoice(FormEvent $event): void
    {
        $form = $event->getForm();
        $user = $event->getData();
        if (!$user instanceof User || !$form->has('reportSkipWeekends')) {
            return;
        }

        $choice = (bool) $form->get('reportSkipWeekends')->getData();
        $mode = $form->get('reportSkipWeekendsMode')->getData();
        $user->setReportSkipWeekends($mode === 'custom' ? $choice : null);
    }
}
