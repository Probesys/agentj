<?php

namespace App\Form;

use App\Entity\Domain;
use App\Validator\Constraints\SenderAddress;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{email: string, domain?: Domain}>
 */
class SenderRuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', TextType::class, [
            'label' => new TranslatableMessage('Generics.fields.sender'),
            'constraints' => [new NotBlank(), new SenderAddress()],
        ]);

        if ($options['is_admin'] === true) {
            $builder->add('domain', EntityType::class, [
                'class' => Domain::class,
                'choices' => $options['domains'],
                'label' => new TranslatableMessage('Entities.SenderRule.fields.domain'),
                'placeholder' => new TranslatableMessage('Generics.actions.chooseDomain'),
                'required' => true,
                'attr' => ['class' => 'select2'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'domains' => [],
            'is_admin' => false,
        ]);
        $resolver->setAllowedTypes('domains', 'array');
        $resolver->setAllowedTypes('is_admin', 'bool');
    }
}
