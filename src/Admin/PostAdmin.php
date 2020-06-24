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
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

use Doctrine\ORM\EntityManagerInterface;

class PostAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $builder = $formMapper->getFormBuilder()->getFormFactory()->createBuilder(PostType::class);

        $formMapper
            ->with('Content', ['class' => 'col-md-9'])
                ->add('username', TextType::class)
                ->add('email', EmailType::class)
                ->add('homepage', UrlType::class)
                ->add('text', TextareaType::class)
            ->end();
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
            ->add('text');
    }

    public function prePersist($post)
    {
        $request = $this->getRequest();
        $uniqid = $request->query->get('uniqid');
        $email = $request->request->get($uniqid)['email'];

        $container = $this->getConfigurationPool()->getContainer();
        $em = $container->get('doctrine');
        $user = $em->getRepository(User::class)->loadUserByUsername($email);

        $post->setUser($user);
        $post->setSlug($post->getSlugify()->slugify(substr($post->getText(), 0, 20)));
    }

    public function toString($object)
    {
        return $object instanceof Post
            ? $object->getSlug()
            : 'Post';
    }

    private $slugify;
}