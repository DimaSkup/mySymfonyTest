<?php

namespace App\Form;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    private $options;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;

        $builder
            ->add('username', TextType::class)
            ->add('email',    TextType::class)
            ->add('homepage', TextType::class, ['required' => false])
            ->add('text',     TextareaType::class)
            ->add('image', FileType::class, [
                        // make it optional so you don't have to re-upload the Image file
                        // every time you edit the Post detail
                        'required' => false,
            ]);

        $builder->get('image')
            ->addModelTransformer(new CallbackTransformer(
                function ($imageAsFile)
                {
                    return null;
                },
                function ($imageFile)       // in this callback function we return string which contains path to the image
                {
                    if (null !== $imageFile)    // if the form contains an image
                    {
                        return $imageFile;  // return UploadedFile object
                    }
                    else if (null !== $this->options['data']->getImage())   // if the form doesn't contain an image but an image has already been attached to the post before
                    {
                        return $this->options['images_directory'].'/'.$this->options['data']->getImage();   // return path to the image catalog + image file name
                    }
                    else    // the form doesn't contain an image and no images are attached to the post
                    {
                        return $this->options['images_directory'].'/default_image.png';     // return path to the default image
                    }
                }
            ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Post::class
        ]);

        $resolver->setRequired([
            'images_directory',     // this option contains the path to the image directory
        ]);
    }
}