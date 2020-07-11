<?php


namespace App\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $locale = $request->query->get('lang');     // get the user's locale from the request

        if (!$locale)       // if the user's locale is not placed in the request
        {
            $locale = $request->cookies->get('locale');     // get the user's locale from the cookie

            if(!$locale)    // if the user's locale is not saved in the cookie
                $locale = 'ua';     // set the user's locale by default
        }

        // save the user's locale in a cookie
        $response = new Response('Content', Response::HTTP_OK, ['content-type' => 'text/html']);
        $response->headers->setCookie(new Cookie('locale', $locale, strtotime('now + 30 days')));
        $response->sendHeaders();

        $request->setLocale($locale);
    }

    static public function getSubscribedEvents()
    {
        return [
          KernelEvents::REQUEST => [['onKernelRequest', 20]]
        ];
    }
}