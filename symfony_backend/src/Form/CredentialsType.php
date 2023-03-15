<?php

namespace App\Form;

use App\Entity\Form\UserCredentials;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

class CredentialsType extends AbstractType{
    
    public function buildForm(FormBuilderInterface $builder, array $options) : void {

        if($options['disabled'] === null)
            $options['disabled'] = false;

        $builder
            ->add('email', TextType::class, ['disabled' => $options['disabled']])
            ->add('name', TextType::class, ['disabled' => $options['disabled']])
            ->add('zip', IntegerType::class, ['disabled' => $options['disabled']])
            ->add('place', TextType::class, ['disabled' => $options['disabled']])
            ->add('phone', TextType::class, ['disabled' => $options['disabled']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => UserCredentials::class
        ]);
    }
}

?>