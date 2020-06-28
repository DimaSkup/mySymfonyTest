<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ResetPasswordRequestRepository")
 */
class ResetPasswordRequest
{

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
}
