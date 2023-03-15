<?php

namespace App\Form;

use App\Entity\Form\UserPassword;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordRType extends AbstractType{
    
    public function buildForm(FormBuilderInterface $builder, array $options) : void {

        if($options['hidden'] === null)
            $options['hidden'] = false;
        if($options['disabled'] === null)
            $options['disabled'] = false;

        if(!$options['hidden']){
            $builder
                ->add('password', PasswordType::class, ['disabled' => $options['disabled']])
                ->add('repeat', PasswordType::class, ['disabled' => $options['disabled']])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => UserPassword::class,
            'hidden' => false
        ]);
    }
}

?>