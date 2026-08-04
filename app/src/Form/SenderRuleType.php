<?php

namespace App\Form;

use App\Validator\Constraints\SenderAddress;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{email: string}>
 */
class SenderRuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', TextType::class, [
            'label' => new TranslatableMessage('Generics.fields.sender'),
            'constraints' => [new NotBlank(), new SenderAddress()],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_token_id' => 'sender_rule',
            'attr' => [
                'class' => 'modal-ajax-form',
            ],
        ]);
    }
}
