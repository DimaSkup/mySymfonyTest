<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="user")
 * @UniqueEntity(fields={"email"}, message="You already have an account")
 */
class User implements UserInterface
{
    public const GITHUB_OAUTH = 'Github';
    public const GOOGLE_OAUTH = 'Google';

    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct()
    {
        $this->setRoles([self::ROLE_USER]);
        $this->setEnabled(false);
        $this->posts = new ArrayCollection();

        // if any parameters are transferred to the constructor, we call the function
        // which will process them, differently we continue work of the constructor
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this, $f='__construct'.$i))  // the case of calling the constructor with a certain number of parameters
        {
            call_user_func_array(array($this, $f), $a);
        }
        else        // the case of calling the constructor without parameters
        {

            $this->setUsername('default_username');
            $this->setOauthType('legasy');
            $this->lastLoginTime = new DateTime('now');
        }
        $this->comments = new ArrayCollection();
    }

    /**
     * Another constructor for OAuth Authentication
     *
     * @param $clientId
     * @param string $email
     * @param string $username
     * @param string $oauthType
     * @param array $roles
     */
    public function __construct5(
        $clientId,
        string $email,
        string $username,
        string $oauthType,
        array $roles
    )
    {
        $this->setClientId($clientId);
        $this->setEmail($email);
        $this->setUsername($username);
        $this->setOauthType($oauthType);
        $this->lastLoginTime = new DateTime('now');
    }

    /**
     * @param int $clientId
     * @param string $email
     * @param string $username
     *
     * @return User
     */
    public static function fromGoogleRequest(
        string $clientId,
        string $email,
        string $username
    ): User
    {
        return new self(
            $clientId,
            $email,
            $username,
            self::GOOGLE_OAUTH,
            [self::ROLE_USER]
        );
    }

    /**
     * @param int $clientId
     * @param string $email
     * @param string $username
     *
     * @return User
     */
    public static function fromGithubRequest(
        int $clientId,
        string $email,
        string $username
    ):  User
    {
        return new self(
            $clientId,
            $email,
            $username,
            self::GITHUB_OAUTH,
            [self::ROLE_USER]
        );
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getClientId(): int
    {
        return $this->clientId;
    }

    /**
     * @param int $clientId
     * @return $this
     */
    public function setClientId($clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     *
     * @return string
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @see UserInterface
     *
     * @return array
     */
    public function getRoles(): array
    {
        return [
            'ROLE_USER'
        ];
    }

    /**
     * @param array $roles
     *
     * @return User
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see UserInterface
     *
     * @return string
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    /**
     * @param string $password
     *
     * @return User
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     *
     * @return User
     */
    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @see UserInterface
     * @return null
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * @return string
     */
    public function getConfirmationCode(): string
    {
        return $this->confirmationCode;
    }

    /**
     * @param string $confirmationCode
     *
     * @return User
     */
    public function setConfirmationCode(string $confirmationCode): self
    {
        $this->confirmationCode = $confirmationCode;
        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return User
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * @param Post $post
     * @return $this
     */
    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post))
        {
            $this->posts[] = $post;
            $post->setUser($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->contains($post))
        {
            $this->posts->removeElement($post);
            if ($post->getUser() === $this)
                $post->setUser(null);
        }

        return $this;
    }


    /**
     * @return string
     */
    public function getOauthType(): string
    {
        return $this->oauthType;
    }

    /**
     * @param string $oauthType
     * @return $this
     */
    public function setOauthType(string $oauthType): self
    {
        $this->oauthType = $oauthType;
        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getLastLoginTime(): DateTimeInterface
    {
        return $this->lastLoginTime;
    }

    /**
     * @param DateTimeInterface $lastLoginTime
     * @return $this
     */
    public function setLastLoginTime(DateTimeInterface $lastLoginTime): self
    {
        $this->lastLoginTime = $lastLoginTime;
        return $this;
    }

    /**
     * @param string $browserData
     * @return User
     */
    public function setUserBrowserData(string $browserData): self
    {
        $this->userBrowserData = $browserData;
        return $this;
    }

    /**
     * @param void
     * @return string
     */
    public function getUserBrowserData(): string
    {
        return $this->userBrowserData;
    }

    /**
     * @param string $userIp
     * @return User
     */
    public function setUserIp(string $userIp): self
    {
        $this->userIp = $userIp;
        return $this;
    }

    /**
     * @param void
     * @return string
     */
    public function getUserIp(): string
    {
        return $this->userIp;
    }


    //*****************************************
    //
    //    Functions for work with comments
    //
    //*****************************************

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }



    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $clientId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string", nullable=true)
     */
    private $password;

    /**
     * @var string|null
     * @Assert\NotBlank()
     */
    private $plainPassword;

    /**
     * @var array
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $confirmationCode;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    /**
     * OneToMany(targetEntity=Post::class, mappedBy="User")
     */
    private $posts;

    /**
     * OneToMany(targetEntity=ResetPasswordRequest::class, mappedBy="User")
     */
    private $resetToken;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $userBrowserData;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $userIp;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $oauthType;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     */
    private $lastLoginTime;

    /**
     * @var Comment[]
     *
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="user")
     */
    private $comments;



}