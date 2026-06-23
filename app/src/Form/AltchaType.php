<?php

declare(strict_types=1);

namespace App\Form;

use App\Validator\Altcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends AbstractType<TextType>
 */
class AltchaType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'timeout' => null,
            'constraints' => new Altcha(),
        ]);

        $resolver->setAllowedTypes('timeout', ['null', 'integer']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['timeout'] = $options['timeout'];
        $view->vars['challenge_url'] = $this->router->generate('altcha_challenge');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'altcha';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }
}
