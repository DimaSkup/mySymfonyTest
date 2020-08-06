<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ResetPasswordRequestRepository")
 */
class ResetPasswordRequest
{
    /**
     * ResetPasswordRequest constructor.
     * @param string $email
     * @param string $hashedToken
     * @param \DateTimeImmutable $expiresAt
     */
    public function __construct(string $email, string $hashedToken, \DateTimeImmutable $expiresAt)
    {
        $this->email = $email;
        $this->token = $hashedToken;
        $this->expiresAt = $expiresAt;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }




    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $email;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime")
     */
    private $expiresAt;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $token;
}
