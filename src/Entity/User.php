<?php
namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use \Serializable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="Users")
 */
class User implements UserInterface, EquatableInterface, Serializable
{

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=64, unique=true, nullable=false)
     */
    private $username;

    /**
     * @ORM\OneToOne(targetEntity="SafeDatabase")
     */
    private $safeDatabase;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $mfaKey;

    /**
     * @ORM\Column(type="text")
     */
    private $publicKey;


    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        return array('ROLE_USER');
    }

    public function getPassword(): string
    {
        return '';
    }

    public function getSalt(): string
    {
        return '';
    }

    public function eraseCredentials(): void
    {
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if ($this->username == $user->getUsername()) {
            return true;
        }
        return false;
    }

    public function serialize(): string
    {
        return serialize(array(
            $this->username
        ));
    }

    public function unserialize($serialized): void
    {
        list($this->username) = unserialize($serialized);
    }

    public function setUsername($username): User
    {
        $this->username = $username;

        return $this;
    }

    public function setSafeDatabase(SafeDatabase $safeDatabase = null): User
    {
        $this->safeDatabase = $safeDatabase;

        return $this;
    }

    public function getSafeDatabase(): SafeDatabase
    {
        return $this->safeDatabase;
    }

    public function setMfaKey(string $mfaKey): User
    {
        $this->mfaKey = $mfaKey;

        return $this;
    }

    public function getMfaKey(): string
    {
        return $this->mfaKey;
    }

    public function setPublicKey(string $publicKey): User
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}
