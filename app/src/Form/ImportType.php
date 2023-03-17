<?php

namespace App\Form;

use App\Entity\Domain;
use App\Repository\DomainRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportType extends AbstractType {

    private DomainRepository $domainRepository;

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('attachment', FileType::class, ['label' => false])
                ->add('domains', EntityType::class, [
                    'class' => Domain::class,
                    'multiple' => true,
                    'attr' => ['class' => 'select2'],
                    'query_builder' => function (DomainRepository $rep) {
                        return $rep->createQueryBuilder('d')
                                ->leftJoin('d.users', 'u')
                                ->where('d.active = 1 ')
                                ->orderBy('d.domain', 'asc');
                    },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => null,
            'user' => null
        ]);
    }

}
