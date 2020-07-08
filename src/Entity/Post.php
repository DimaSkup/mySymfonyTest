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
        $this->setIsModerated(false);
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
     * @return Post
     */
    public function setUsername($username): self
    {
        $this->username = $username;
        return $this;
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
     * @return Post
     */
    public function setEmail($email): Post
    {
        $this->email = $email;
        return $this;
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
     * @return Post
     */
    public function setHomepage($homepage): self
    {
        $this->homepage = $homepage;
        return $this;
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
     * @return Post
     */
    public function setText($text): Post
    {
        $this->text = $text;
        return $this;
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
     * @return Post
     */
    public function setSlug($slug): Post
    {
        $this->slug = $slug;
        return $this;
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
     * @return Post
     */
    public function setCreatedAt(\DateTimeInterface $created_at): Post
    {
        $this->created_at = $created_at;
        return $this;
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
     * @param string|null $imageFilename
     * @return Post
     */
    public function setImage(?string $imageFilename): self
    {
        $this->image = $imageFilename;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsModerated(): bool
    {
        return $this->is_moderated;
    }

    /**
     * @param bool $is_moderated
     * @return Post
     */
    public function setIsModerated(bool $is_moderated): self
    {
        $this->is_moderated = $is_moderated;
        return $this;
    }



    //**************************************
    //
    //       The data of the class
    //
    //**************************************

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
     * @var string|null
     * @Assert\NotBlank(message="plz upload an image")
     * @Assert\Image()
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;


    /**
     * @var bool
     * @ORM\Column(name="is_moderated", type="boolean", nullable=false)
     */
    private $is_moderated;  // a boolean flag which indicates whether the post has been moderated

}

