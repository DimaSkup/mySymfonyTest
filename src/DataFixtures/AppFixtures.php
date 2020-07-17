<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;

use Cocur\Slugify\Slugify;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use App\Repository\UserRepository;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class AppFixtures extends Fixture
{
    private $faker;
    private $slug;
    private $passwordEncoder;
    private $userRepository;
    private $fakePostsCount;
    private $fakeUsersCount;



    public function __construct(Slugify $slugify, UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->faker = Factory::create();
        $this->slug = $slugify;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;

        $this->fakePostsCount = 50;
        $this->fakeUsersCount = 5;
    }

    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
        $this->loadPosts($manager);
    }

    public function loadPosts(ObjectManager $manager)
    {
        for ($i = 1; $i <= $this->fakePostsCount; $i++)
        {
            $post = new Post();
            $post->setUsername($this->faker->text(10))
                 ->setEmail($this->faker->numberBetween(0, 6)."@gmail.com")
                 ->setHomepage($this->faker->text(10)."com")
                 ->setText($this->faker->text(500))
                 ->setSlug($this->slug->slugify(substr($post->getText(), 0, 20)))
                 ->setCreatedAt($this->faker->dateTime)
                 ->setUser($this->userRepository->findOneBy(['email' => rand(1, $this->fakeUsersCount).'@gmail.com']))
                 ->setIsModerated(true)
                 ->setImage('animal_'.mt_rand(1, 10).'.jpg');

            $manager->persist($post);
        }
        $manager->flush();
    }

    public function loadUsers(ObjectManager $manager)
    {


        for ($i = 1; $i <= $this->fakeUsersCount; $i++)
        {
            $user = new User();
            // Encode the plain password
            $encodedPassword = $this->passwordEncoder->encodePassword(
                $user,
                '12345'
            );
            $user->setEmail($i.'@gmail.com')
                 ->setPassword($encodedPassword)       // set encoded password as a user password
                 ->setRoles([User::ROLE_USER])
                 ->setEnable(true)
                 ->setIsVerified(true);

            $manager->persist($user);
        }
        $manager->flush();
    }
}