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
                            'data' => null,
                            ])
                ->add('is_moderated', BooleanType::class)
            ->end();
/*
        $formMapper->get('image')
            ->addViewTransformer()
  */

        $formMapper->get('image')
            ->addModelTransformer(new CallbackTransformer(
                function ($imageAsFile)
                {
                    return null;
                },
                function ($emptyImageFieldAsString)
                {

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
        if ($imageFile) {
            //$originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            // this is needed to safely include the file name as part of the URL

            //$safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            //$newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
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
        //$em = $container->get('doctrine');          // doctrine entity manager
        $request = $this->getRequest();                 // Request object
        $uniqid = $request->query->get('uniqid');
        $uniqidDataArray = $request->request->get($uniqid);
        $imageFile = $request->files->get($uniqid)['image'];

        if (null !== $imageFile)     // the administrator uploaded a new image while editing
        {
            // this condition is needed because the 'image' field is not required
            // so the JPG|PNG|GIF file must be processed only when a file is uploaded
            if ($imageFile)
            {
                //$originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL

                //$safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                //$newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $newFilename = md5(uniqid()).'.'.$imageFile->guessExtension();

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
        }
        else    // use an image that was previously attached to the post
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