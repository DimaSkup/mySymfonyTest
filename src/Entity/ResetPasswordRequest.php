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

    private $email;
    private $expiresAt;
    private $token;

    public function __construct(string $email,  string $hashedToken, \DateTimeInterface $expiresAt)
    {
        $this->email = $email;
        $this->token = $hashedToken;
        $this->expiresAt = $expiresAt;

    }
}
