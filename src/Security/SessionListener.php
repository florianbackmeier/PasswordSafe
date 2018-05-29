<?php
namespace App\Security;

use App\Security\Authentication\DeviceType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SessionListener implements EventSubscriberInterface
{
    private $session;
    private $securityChecker;
    private $tokenStorage;

    public function __construct(SessionInterface $session, AuthorizationCheckerInterface $securityChecker, TokenStorageInterface $tokenStorage)
    {
        $this->session = $session;
        $this->securityChecker = $securityChecker;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$this->securityChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }
        if (!$this->session->has('EXPIRES') || $this->session->get('EXPIRES') < time()) {
            $this->tokenStorage->setToken(null, null);
            $this->session->invalidate();
            $event->setController(function () {
                return new RedirectResponse('/');
            });
            return;
        }
        if ($this->tokenStorage->getToken()->getDeviceType() == DeviceType::SECURE) {
            $this->session->set('EXPIRES', time() + 240 * 60);
        } else {
            $this->session->set('EXPIRES', time() + 30 * 60);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
        );
    }
}
