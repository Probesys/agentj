<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Captcha\Bundle\CaptchaBundle\Form\Type\CaptchaType;

class CaptchaFormType extends AbstractType {

  public function buildForm(FormBuilderInterface $builder, array $options) {

    $builder->add('email', null, array(
            ))
            ->add('emailEmpty', null, [
                'label' => false,
                'required' => false,
                'attr' => ['style' => 'display:none']
            ]);
  }


  public function configureOptions(OptionsResolver $resolver) {
    $resolver->setDefaults([
        'data_class' => null
    ]);
  }  
}
