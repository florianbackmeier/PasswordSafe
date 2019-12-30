<?php
namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HeaderSecurityService implements EventSubscriberInterface
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $responseHeaders = $event->getResponse()->headers;
        $responseHeaders->set('Content-Security-Policy', "default-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; img-src 'self' data:; connect-src 'self'; font-src 'self' fonts.googleapis.com fonts.gstatic.com; object-src 'none'; media-src 'self'; frame-src 'none'; manifest-src 'self';");

        $responseHeaders->set('X-Frame-Options', 'SAMEORIGIN');

        $responseHeaders->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $responseHeaders->set('Pragma', 'no-cache');
        $responseHeaders->set('Expires', '0');
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onKernelResponse',
        );
    }
}
