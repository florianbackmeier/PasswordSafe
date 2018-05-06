<?php
namespace App\Security\Authentication;

use App\Security\DatabaseService;
use App\Security\EncryptionService;
use App\Security\Exceptions\InitializationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimpleFormAuthenticatorInterface;

class SafeAuthenticator implements SimpleFormAuthenticatorInterface
{
    private $encryptionService;
    private $databaseService;
    private $mfaService;
    private $session;

    public function __construct(EncryptionService $encryptionService, DatabaseService $databaseService, MfaService $mfaService, SessionInterface $session)
    {
        $this->encryptionService = $encryptionService;
        $this->databaseService = $databaseService;
        $this->mfaService = $mfaService;
        $this->session = $session;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if ( !$userProvider instanceof UserProvider ) {
            throw new AuthenticationException('Wrong userprovider supplied.');
        }
        if ( !$token instanceof UsernameKeyToken ) {
            throw new AuthenticationException('Wrong token supplied.');
        }
        try {
            $user = $userProvider->loadUserByUsername($token->getUsername());
        } catch (UsernameNotFoundException $e) {
            throw new AuthenticationException('Invalid username or password or authentication code');
        }

        if ($user->getSafeDatabase() == null) {
            throw new InitializationException('Please initialize your account.');
        }

        $key = $this->encryptionService->generateKey($token->getCredentials(), $user->getSafeDatabase()->getSalt(), $user->getSafeDatabase()->getKeyIterations());
        if ($this->encryptionService->isValidKey($key, $user->getSafeDatabase()) && $this->mfaService->validateOTP($user, $token->getMfaCode())) {
            $userToken = new UsernameKeyToken($user, $key, $providerKey, $user->getRoles());
            if ($token->getDeviceType() == DeviceType::SECURE) {
                $this->session->set('EXPIRES', time() + 240 * 60);
                $userToken->setDeviceType(DeviceType::SECURE);
            } else {
                $this->session->set('EXPIRES', time() + 30 * 60);
            }
            return $userToken;
        }

        throw new AuthenticationException('Invalid username or password or authentication code');
    }

    public function supportsToken(TokenInterface $token, $providerKey): bool
    {
        return $token instanceof UsernameKeyToken
        && $token->getProviderKey() === $providerKey;
    }

    public function createToken(Request $request, $username, $password, $providerKey): UsernameKeyToken
    {
        $token = new UsernameKeyToken($username, $password, $providerKey);
        $token->setMfaCode($request->request->get('mfaCode'));
        if ($request->request->get('deviceType') == DeviceType::SECURE) {
            $token->setDeviceType(DeviceType::SECURE);
        }
        return $token;
    }
}
