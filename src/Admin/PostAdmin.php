<?php


namespace App\Admin;

use App\Entity\Post;
use App\Entity\User;


use App\Form\PostType;
use App\Form\UserType;
use Cocur\Slugify\Slugify;
use PhpParser\Comment;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostAdmin extends AbstractAdmin
{
    public function __construct($code, $class, $baseControllerName, Slugify $slugify)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->slugify = $slugify;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('Content', ['class' => 'col-md-9'])
                ->add('username', TextType::class)
                ->add('email', EmailType::class)
                ->add('homepage', UrlType::class)
                ->add('text', TextareaType::class)
                ->add('image', FileType::class, [
                            'label' => 'Image (JPG|PNG|GIF file)',
                            'required' => false,
                            //'data' => null,
                            ])
                ->add('is_moderated', BooleanType::class)
            ->end();
/*
        $formMapper->get('image')
            ->addViewTransformer()
  */

        $formMapper->get('image')
            ->addModelTransformer(new CallbackTransformer(
                function ($image)
                {
                    return;
                },
                function ($image)
                {
                    $request = $this->getRequest();
                    $uniqid = $request->query->get('uniqid');
                    $imageFile = $request->files->get($uniqid)['image'];
                    $container = $this->getConfigurationPool()->getContainer();
                    $pathToImageDir = $container->getParameter('images_directory');

                    if (null !== $imageFile)
                        return $imageFile;
                    else if (null !== $this->subject->getImage())
                    {
                        $postImageFilename = $this->subject->getImage();
                        return $pathToImageDir.'/'.$postImageFilename;
                    }
                    else // null === $this->subject->getImage() && null === $imageFile
                    {
                        return $pathToImageDir.'/default_image.png';
                    }
                }
            ));

    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('username')
            ->add('email')
            ->add('text');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('username')
            ->add('email')
            ->add('text')
            ->add('is_moderated');
    }

    public function prePersist($post)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $em = $container->get('doctrine');      // doctrine entity manager

        $request = $this->getRequest();
        $uniqid = $request->query->get('uniqid');
        $uniqidDataArray = $request->request->get($uniqid);

        // File upload handling
        $imageFile = $request->files->get($uniqid)['image'];
        //$imageFilename = $imageFile->getFilename();


        // this condition is needed because the 'image' field is not required
        // son the JPG|PNG|GIF file must be processed only when a file is uploaded
        if ($imageFile)
        {
            $newFilename = md5(uniqid()) . '.' . $imageFile->guessExtension();

            // Move the file to the directory where images are stored
            try {
                $imageFile->move(
                    $container->getParameter('images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
            }

            // updates the 'imageFilename' property to store the JPG|PNG|GIF file name
            // instead of its contents
            $post->setImage($newFilename);
        }
        else
            $post->setImage(null);


        $user = $em->getRepository(User::class)->loadUserByUsername($uniqidDataArray['email']);

        $post->setUser($user);
        if ($uniqidDataArray['is_moderated'] == 2)  // "no" option selected (second parameter in the field)
            $post->setIsModerated(false);
        else
            $post->setIsModerated(true);            // "yes" option selected (first parameter in the field)

        $post->setSlug($this->slugify->slugify(substr($uniqidDataArray['text'], 0, 20)));
    }

    public function preUpdate($post)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $request = $this->getRequest();                 // Request object
        $uniqid = $request->query->get('uniqid');
        $uniqidDataArray = $request->request->get($uniqid);
        $imageFile = $request->files->get($uniqid)['image'];

        // this condition is needed because the 'image' field is not required
        // so the JPG|PNG|GIF file must be processed only when a file is uploaded
        if ($imageFile)         // the administrator uploaded a new image while editing
        {
            $newFilename = md5(uniqid()) . '.' . $imageFile->guessExtension();

            // Move the file to the directory where images are stored
            try
            {
                $imageFile->move(
                    $container->getParameter('images_directory'),
                    $newFilename
                );
            }
            catch (FileException $e)
            {
                // ... handle exception if something happens during file upload
            }

            // updates the 'imageFilename' property to store the JPG|PNG|GIF file name
            // instead of its contents
            $post->setImage($newFilename);
        }
        // this means that no image has been transferred to the form,
        // use an image that was previously attached to the post
        else
        {
            $post->setImage(basename($post->getImage()));
        }



        if ($uniqidDataArray['is_moderated'] == 2)  // "no" option selected (second parameter in the field)
            $post->setIsModerated(false);
        else
            $post->setIsModerated(true);            // "yes" option selected (first parameter in the field)

        $post->setSlug($this->slugify->slugify(substr($uniqidDataArray['text'], 0, 20)));
    }


    public function toString($object)
    {
        return $object instanceof Post
            ? $object->getSlug()
            : 'Post';
    }



    /**
     * @var Slugify
     */
    private $slugify;

    /**
     * @var DataTransformerInterface
     */
    private $transformer;

}