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

        $builder
            ->add('username', TextType::class, ['label' => ''])
            ->add('email',    TextType::class, ['label' => ''])
            ->add('homepage', TextType::class, ['label' => '', 'required' => false])
            ->add('text',     TextareaType::class, ['label' => ' '])
            ->add('image', FileType::class, [
                        'label' => 'Image (JPG|PNG|GIF file)',
                        // make it optional so you don't have to re-upload the Image file
                        // every time you edit the Post detail
                        'required' => false,
            ]);


        $this->options = $options;
/*
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event)
        {   // beginning of the callback function
            $this->entityManager->flush();
        });
*/


        $builder->get('image')
            ->addModelTransformer(new CallbackTransformer(
                function ($imageAsFile)
                {
                    return null;
                },
                function ($imageFile) {
                    if (null !== $imageFile) {
                        $imageDirectoryPath = $this->options['images_directory'];   // get the path to the image directory

                        $newFilename = md5(uniqid()) . '.' . $imageFile->guessExtension();

                        // Move the file to the directory where images are stored
                        try {
                            $imageFile->move(
                                $imageDirectoryPath,
                                $newFilename
                            );
                        } catch (FileException $e) {
                            // ... handle exception if something happens during file upload
                        }

                        return $newFilename;
                    } else
                    {
                        return ' ';
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
            'images_directory',
        ]);
    }
}