<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Util\Email;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<User>
 */
class UserAliasType extends AbstractType
{
    public function __construct(
        private DomainRepository $domainRepository,
        private UserRepository $userRepository,
        private Security $security,
        private TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => new TranslatableMessage('Entities.User.fields.alias'),
            'required' => true,
        ]);

        /** @var User $originalUser */
        $originalUser = $options['original_user'];

        // Registered with a positive priority so that it runs before the
        // validation listener: the alias must be fully initialized before the
        // entity constraints are checked.
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($originalUser): void {
            /** @var User $alias */
            $alias = $event->getData();
            $emailField = $event->getForm()->get('email');
            $aliasEmail = $alias->getEmail() ?? '';

            $domainName = Email::extractDomain($aliasEmail) ?? '';
            $domain = $this->domainRepository->findOneByDomain($domainName);
            if (!$domain || !$this->security->isGranted('DOMAIN_ACCESS', $domain)) {
                $emailField->addError(new FormError(
                    $this->translator->trans('Generics.flash.domainNotExist'),
                ));
                return;
            }

            if ($this->userRepository->findOneBy(['email' => $aliasEmail])) {
                $emailField->addError(new FormError(
                    $this->translator->trans('Generics.flash.aliasAlreadyExist'),
                ));
                return;
            }

            $alias->setUsername($aliasEmail);
            $alias->setDomain($domain);
            $alias->setOriginalUser($originalUser);
        }, 10);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'user_alias',
        ]);

        $resolver->setRequired('original_user');
        $resolver->setAllowedTypes('original_user', User::class);
    }
}
