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

    public function __serialize(): array
    {
        $array = parent::__serialize();
        array_push($array, $this->key);
        return $array;
    }

    public function __unserialize(array $data): void
    {
        $this->key = array_pop($data);
        parent::__unserialize($data);
    }
}
