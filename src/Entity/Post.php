<?php

namespace App\Entity;

use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Cocur\Slugify\Slugify;

/**
 * @ORM\Entity(repositoryClass=PostRepository::class)
 * @ORM\Table(name="posts")
 */
class Post
{
    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->slugify = new Slugify();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     * @return void
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return void
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * @param mixed $homepage
     * @return void
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     * @return void
     */
    public function setText($text): void
    {
        $this->text = $text;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $slug
     * @return void
     */
    public function setSlug($slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    /**
     * @param \DateTimeInterface $created_at
     * @return void
     */
    public function setCreatedAt(\DateTimeInterface $created_at): void
    {
        $this->created_at = $created_at;
    }


    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return $this
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $imageFilename
     * @return Post
     */
    public function setImage(string $imageFilename): self
    {
        $this->image = $imageFilename;
        return $this;
    }





    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Assert\Length(min=3, max=30)
     */
    private $username;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $homepage;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $text;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $slug;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="Post")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @var string
     * @Assert\NotBlank(message="plz upload an image")
     * @Assert\Image()
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

}

