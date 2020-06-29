<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, ['label' => ''])
            ->add('email',    TextType::class, ['label' => ''])
            ->add('homepage', TextType::class, ['label' => '', 'required' => false])
            ->add('text',     TextareaType::class, ['label' => ' '])
            ->add('image', FileType::class, [
                'label' => 'Image (JPG|PNG|GIF file)',
                /*
                // unmapped means that this field is not associated to any entity property
                'mapped' => false,
                // make it optional so you don't have to re-upload the Image file
                // every time you edit the Post details
                'required' => false,
                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File ([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'application/jpg',
                            'application/png',
                            'application/gif'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPG|PNG|GIF file',
                    ])
                ],
                */
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Post::class
        ]);
    }
}