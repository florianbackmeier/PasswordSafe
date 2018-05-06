<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity
 * @ORM\Table(name="SafeDatabases")
 */
class SafeDatabase implements JsonSerializable
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private $salt = '';

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $keyIterations = 60000;

    /**
     * @ORM\Column(name="`data`", type="text", nullable=false)
     */
    private $data = '';

    public function setSalt(string $salt)
    {
        $this->salt = bin2hex($salt);
    }

    public function getSalt()
    {
        return hex2bin($this->salt);
    }

    public function setData(string $data)
    {
        $this->data = bin2hex($data);
    }

    public function getData()
    {
        return hex2bin($this->data);
    }

    public function jsonSerialize()
    {
        $objectArray = [];
        foreach ($this as $key => $value) {
            $objectArray[$key] = $value;
        }
        return $objectArray;
    }

    public function deSerialize($obj)
    {
        $this->salt = $obj->salt;
        $this->data = $obj->data;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setKeyIterations(int $keyIterations): void
    {
        $this->keyIterations = $keyIterations;
    }

    public function getKeyIterations(): int
    {
        return $this->keyIterations;
    }
}
