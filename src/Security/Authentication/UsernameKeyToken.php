<?php
namespace App\Security\Authentication;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UsernameKeyToken extends UsernamePasswordToken
{
    private $mfaCode;
    private $deviceType;

    public function setMfaCode($code): void
    {
        $this->mfaCode = $code;
    }

    public function getMfaCode(): string
    {
        return $this->mfaCode;
    }

    public function setDeviceType($deviceType): void
    {
        $this->deviceType = $deviceType;
    }

    public function getDeviceType()
    {
        return $this->deviceType;
    }

    public function eraseCredentials(): void
    {
    }

    public function serialize(): string
    {
        return serialize(array($this->deviceType, parent::serialize()));
    }

    public function unserialize($serialized)
    {
        list($this->deviceType, $parentStr) = unserialize($serialized);
        parent::unserialize($parentStr);
    }
}
