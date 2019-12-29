<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="SharedPasswords")
 */
class SharedPassword
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="origin", referencedColumnName="username")
     */
    private $origin;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="receiver", referencedColumnName="username")
     */
    private $receiver;

    /**
     * @ORM\Column(type="text")
     */
    private $encryptedData;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $attributes;

    /**
     * @ORM\Column(type="text")
     */
    private $type = SharedPasswordType::SHARED;

    public function setId(int $id): SharedPassword
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setOrigin(User $origin = null): SharedPassword
    {
        $this->origin = $origin;

        return $this;
    }

    public function getOrigin(): User
    {
        return $this->origin;
    }

    public function setReceiver(User $receiver = null): SharedPassword
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getReceiver(): User
    {
        return $this->receiver;
    }

    public function setEncryptedData(string $encryptedData): SharedPassword
    {
        $this->encryptedData = bin2hex($encryptedData);
        return $this;
    }

    public function getEncryptedData(): string
    {
        return hex2bin($this->encryptedData);
    }

    public function setAttributes(object $attributes = null): SharedPassword
    {
        $this->attributes = json_encode($attributes);

        return $this;
    }

    public function getAttributes(): object
    {
        return json_decode($this->attributes);
    }

    public function setType(string $type): SharedPassword
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): SharedPasswordType
    {
        return $this->type;
    }
}
