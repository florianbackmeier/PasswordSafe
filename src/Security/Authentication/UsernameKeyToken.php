<?php
namespace App\Security\Authentication;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class UsernameKeyToken extends PostAuthenticationGuardToken
{
    private $key;

    public function __construct(UserInterface $user, string $key, string $providerKey, array $roles)
    {
        parent::__construct($user, $providerKey, $roles);

        $this->key = $key;
    }

    public function getCredentials() {
        return $this->key;
    }

    public function serialize(): string
    {
        return serialize(array($this->key, parent::serialize()));
    }

    public function unserialize($serialized)
    {
        list($this->key, $parentStr) = unserialize($serialized);
        parent::unserialize($parentStr);
    }
}
