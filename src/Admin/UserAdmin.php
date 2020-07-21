<?php


namespace App\Admin;

use App\Entity\User;

use FOS\UserBundle\Model\UserManagerInterface;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


final class UserAdmin extends AbstractAdmin
{
    public function __construct($code, $class, $baseControllerName, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General')
                ->add('email', TextType::class)
                ->add('plainPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'required' => false,
                ])
                ->add('enabled', CheckBoxType::class, [
                    'required' => false
                ])
            ->end();

        $formMapper->get('plainPassword')
            ->addModelTransformer(new CallbackTransformer(
                function ($password)
                {
                },
                function ($password)
                {
                    if ($password)
                        return $password;
                    else
                        return $this->subject->getPassword();


                    /*
                    dd($this->subject);
                    $request = $this->getRequest();
                    $uniqid = $request->query->get('uniqid');
                    $container = $this->getConfigurationPool()->getContainer();
                    //$pathToImageDir = $container->getParameter('images_directory');

                   */
                }
            ));
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('email');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('email')
            ->add('enabled');
    }

    public function prePersist($user)
    {
        $request = $this->getRequest();
        $uniqid = $request->query->get('uniqid');
        $password = $request->request->get($uniqid)['plainPassword']['first'];

        $user->setPassword($password);

        return $user;
    }

    public function preUpdate($user)
    {
        //dd($user);
        if ($user->getPlainPassword() !== $user->getPassword())
        {
            $password = $this->passwordEncoder->encodePassword(
                $user,
                $user->getPlainPassword()
            );
            $user->setPassword($password);
            $user->setPlainPassword(null);
        }
        else    // $user->getPlainPassword() === $user->getPassword()
        {
            $user->setPlainPassword(null);
        }

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

    public function toString($object)
    {
        return $object instanceof User
            ? $object->getUsername()
            : 'User';
    }

    private $userManager;

    /**
     * @var UserPasswordEncoderInterface $passwordEncoder
     */
    private $passwordEncoder;


}