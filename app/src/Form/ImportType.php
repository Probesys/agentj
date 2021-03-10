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

  public function buildForm(FormBuilderInterface $builder, array $options) {
    $user = $options['user'];
    $builder
            ->add('attachment', FileType::class, ['label' => false]);
  }

  public function configureOptions(OptionsResolver $resolver) {
    $resolver->setDefaults([
        'data_class' => null,
        'user' => null
    ]);
  }

}
