<?php

namespace App\Form;

use App\Entity\Post;
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
    private $formData;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('username', TextType::class, ['label' => ''])
            ->add('email',    TextType::class, ['label' => ''])
            ->add('homepage', TextType::class, ['label' => '', 'required' => false])
            ->add('text',     TextareaType::class, ['label' => ' '])
            ->add('image', FileType::class, [
                        'label' => 'Image (JPG|PNG|GIF file)',
                        //'data' => null,
                        'required' => false,
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
        //$this->formData = $options['data'];
        //dd($this->formData);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $post = $event->getData();
            $imageFile = $post['image'];

            $newFilename = md5(uniqid()).'.'.$imageFile->guessExtension();
/*
            // Move the file to the directory where images are stored
            try
            {
                $imageFile->move(
                    $this->('images_directory'),
                    $newFilename
                );
            }
            catch (FileException $e)
            {
                // ... handle exception if something happens during file upload
            }
            dd($imageFile);
*/
        });


        $this->formData = $options['data'];

        $builder->get('image')
            ->addModelTransformer(new CallbackTransformer(
                function ($imageAsFile)
                {
                    return null;
                },
                function ($options)
                {
                    return ' ';
                    //$fullImagePath = $this->formData->getImage();
                    //return $fullImagePath;
/*
                    $container = $this->getConfigurationPool()->getContainer();
                    $request = $this->getRequest();
                    $uniqid = $request->query->get('uniqid');
                    $file = $request->files->get($uniqid)['image'];
                    if (null === $file)
                    {

                        $userEmail = $request->request->get($uniqid)['email'];
                        $post = $container->get('doctrine')->getRepository(Post::class)
                            ->findBy(['email' => $userEmail]);
                        $post = $post[0];

                        $pathToImage = $container->getParameter('images_directory');
                        $fullPathToImage = $pathToImage.'/'.$post->getImage();

                        return $fullPathToImage;
                    }
                    return $file;
*/
                }
            ));

    }


    public function preUpdate($post)
    {
        dd("LOL");
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Post::class
        ]);
    }
}