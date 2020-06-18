<?php


namespace App\Admin;

use App\Entity\User;

use FOS\UserBundle\Model\UserManagerInterface;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


final class UserAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General')
                ->add('email', TextType::class)
                ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class
            ])
            ->end();
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('email');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper->addIdentifier('email');
    }

    public function prePersist($user)
    {
        $request = $this->getRequest();
        $uniqid = $request->query->get('uniqid');
        $password = $request->request->get($uniqid)['plainPassword']['first'];

        dd($password);

        //$user->setPassword($password);

        return $user;
    }

    public function setUserManager(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @return UserManagerInterface
     */
    public function getUserManager()
    {
        return $this->userManager;
    }


    private $userManager;
}