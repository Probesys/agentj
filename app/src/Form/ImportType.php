<?php

namespace App\Form;

use App\Entity\Domain;
use App\Entity\User;
use App\Repository\DomainRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ImportType extends AbstractType
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $builder
            ->add('attachment', FileType::class, ['label' => false])
            ->add('domain', EntityType::class, [
                'class' => Domain::class,
                'label' => new TranslatableMessage('Entities.SenderRule.fields.domain'),
                'multiple' => false,
                'attr' => ['class' => 'select2'],
                'query_builder' => function (DomainRepository $rep) use ($user) {
                    $queryBuilder = $rep->createQueryBuilder('d')
                               ->leftJoin('d.users', 'u')
                               ->where('d.active = 1 ')
                               ->orderBy('d.domain', 'asc');

                    if (!$user->isSuperAdmin()) {
                        $queryBuilder->andWhere('d IN (:allowedDomains)')
                            ->setParameter('allowedDomains', $user->getDomains());
                    }

                    return $queryBuilder;
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
