<?php

namespace App\DataFixtures;

use App\Entity\Post;
use Cocur\Slugify\Slugify;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    private $faker;
    private $slug;

    public function __construct(Slugify $slugify)
    {
        $this->faker = Factory::create();
        $this->slug = $slugify;
    }

    public function load(ObjectManager $manager)
    {
        $this->loadPosts($manager);
    }

    public function loadPosts(ObjectManager $manager)
    {
        for ($i = 1; $i <= 20; $i++)
        {
            $post = new Post();
            $post->setUsername($this->faker->text(10));
            $post->setEmail($this->faker->text(5)."@gmail.com");
            $post->setHomepage($this->faker->text(10)."com");
            $post->setText($this->faker->text(500));
            $post->setSlug($this->slug->slugify(substr($post->getText(), 0, 20)));
            $post->setCreatedAt($this->faker->dateTime);

            $manager->persist($post);
        }
        $manager->flush();
    }
}