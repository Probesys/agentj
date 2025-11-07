<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DomainCustomisationReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('messageAlert', TextareaType::class, [
            'label' => 'Entities.Domain.fields.messageAlert',
            'attr' => [
                'data-controller' => 'ckeditor',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Domain::class,
        ]);
    }
}
