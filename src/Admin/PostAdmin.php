<?php


namespace App\Admin;


use App\Entity\Post;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class PostAdmin extends AbstractAdmin
{

    protected function configureFormFields(FormMapper $formMapper)
    {
       // $formMapper            ->add('text', TextType::class);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
       // $datagridMapper->add('text');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
       //$listMapper->addIdentifier('text');
    }

    public function toString($object)
    {
        return $object instanceof Post
            ? $object->getText()
            : 'ost';
    }
}