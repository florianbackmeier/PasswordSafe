<?php
namespace App\Security\Authentication;

use App\Security\DatabaseService;
use App\Security\EncryptionService;
use App\Security\Exceptions\InitializationException;
use App\Service\CategoryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class SafeAuthenticator implements AuthenticatorInterface
{
    private $router;
    private $encryptionService;
    private $databaseService;
    private $mfaService;
    private $session;
    private $categoryService;

    public function __construct(RouterInterface $router, EncryptionService $encryptionService, DatabaseService $databaseService, MfaService $mfaService, SessionInterface $session, CategoryService $categoryService)
    {
        $this->router = $router;
        $this->encryptionService = $encryptionService;
        $this->databaseService = $databaseService;
        $this->mfaService = $mfaService;
        $this->session = $session;
        $this->categoryService = $categoryService;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse('/login');
    }

    public function supports(Request $request)
    {
        return $request->attributes->get('_route') === 'login' && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        return [
            'username' => $request->request->get('ps_user'),
            'password' => $request->request->get('ps_pass'),
            'mfaCode' => $request->request->get('mfaCode'),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['username']);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if ($user->getSafeDatabase() == null) {
            throw new InitializationException('Please initialize your account.');
        }

        $key = $this->encryptionService->generateKey($credentials['password'], $user->getSafeDatabase()->getSalt(), $user->getSafeDatabase()->getKeyIterations());
        $validPassword = $this->encryptionService->isValidKey($key, $user->getSafeDatabase());
        $validOTP = $this->mfaService->validateOTP($user, $credentials['mfaCode']);

        $user->setCredentials($key);

        return $validPassword && $validOTP;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($exception instanceof InitializationException) {
            $username = $request->request->get('ps_user');
            return new RedirectResponse($this->router->generate('first_login', array('username' => $username)));
        }
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse($this->router->generate('login'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $meta = $this->databaseService->getMeta($token);
        $startCategory = $meta->get('startCategory');
        if (!empty($startCategory)) {
            return new RedirectResponse($this->router->generate('category', array('categorySlug' => $this->categoryService->generateUrlSlug($startCategory))));
        } else {
            return new RedirectResponse($this->router->generate('home'));
        }
    }

    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        $key = $user->getCredentials();
        $user->eraseCredentials();
        return new UsernameKeyToken($user, $key, $providerKey, $user->getRoles());
    }

    public function supportsRememberMe()
    {

        return false;
    }
}
