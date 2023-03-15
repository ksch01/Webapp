<?php

namespace App\Form;

use App\Entity\Form\UserPrivileges;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

class UsergroupType extends AbstractType{
    
    public function buildForm(FormBuilderInterface $builder, array $options) : void {

        if($options['hidden'] === null)
            $options['hidden'] = false;
        if($options['disabled'] === null)
            $options['disabled'] = false;
            
        if(!$options['hidden']){
            $builder->add('group', ChoiceType::class, ['choices' => UserPrivileges::GROUPS, 'disabled' => $options['disabled']]);
        }
    }

    public function configureOptions(OptionsResolver $resolver) : void {
        $resolver->setDefaults([
            'data_class' => UserPrivileges::class,
            'hidden' => false
        ]);
    }
}

?>