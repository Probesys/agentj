<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('attachment', FileType::class, ['label' => false]);

        if (is_array($options['domains'])) {
            $builder->add('domain', EntityType::class, [
                'class' => Domain::class,
                'choices' => $options['domains'],
                'label' => new TranslatableMessage('Entities.SenderRule.fields.domain'),
                'multiple' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'domains' => null,
        ]);

        $resolver->setAllowedTypes('domains', ['array', 'null']);
    }
}
