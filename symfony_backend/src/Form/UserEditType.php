<?php

namespace App\Form;

use App\Entity\Form\UserData;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

class UserEditType extends AbstractType{
    
    public function buildForm(FormBuilderInterface $builder, array $options) : void {

        if($options['signup']){
            $builder
                ->add('credentials', CredentialsType::class, ['disabled' => false])
                ->add('password', PasswordRType::class, ['hidden' =>  false, 'required' => true])
                ->add('agree', CheckboxType::class, ['mapped' => false, 'label' => "I have read and agreed to the terms of use and privacy policy."])
                ->add('submit', SubmitType::class, ['label' => "Signup"])
            ;
            return;
        }

        $privileges = $options['privileges'];

        $builder
            ->add('credentials', CredentialsType::class, ['disabled' => !$privileges['edit_cred']])
            ->add('password', PasswordRType::class, ['hidden' => !$privileges['edit_pass'], 'required' => false])
            ->add('usergroup', UsergroupType::class, ['hidden' => !$privileges['edit_priv']])
        ;

        if($privileges['edit_cred'] || $privileges['edit_pass'] || $privileges['edit_priv'])
            $builder->add('submit', SubmitType::class, ['label' => "Update"]);
    }

    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => UserData::class,
            'privileges' => ['edit_cred' => false, 'edit_pass' => false, 'edit_priv' => false],
            'signup' => false
        ]);
    }
}

?>