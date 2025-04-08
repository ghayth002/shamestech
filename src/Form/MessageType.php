<?php

namespace App\Form;

use App\Entity\Message;
use App\Entity\Question;
use App\Entity\Reponse;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content')
            ->add('sender')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('question', EntityType::class, [
                'class' => Question::class,
                'choice_label' => 'name', // ou 'email'
            ])
            ->add('reponse', EntityType::class, [
                'class' => Reponse::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}
