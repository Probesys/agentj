<?php

namespace App\Form;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class HumanAuthenticationType extends AbstractType
{
    public function __construct(
        #[Autowire(env: 'ALTCHA_KEY_SECRET')]
        private readonly ?string $altchaKeySecret,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'flow',
            ],
            'csrf_token_id' => 'human_auth',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => new TranslatableMessage('Message.HumanAuthentication.toVerify.email'),
        ]);

        if ($this->altchaKeySecret) {
            $builder->add('altcha', AltchaType::class, [
                'timeout' => 30,
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => new TranslatableMessage('Message.HumanAuthentication.toVerify.submit'),
            'attr' => [
                'class' => 'button--primary button--block',
            ],
        ]);
    }
}
