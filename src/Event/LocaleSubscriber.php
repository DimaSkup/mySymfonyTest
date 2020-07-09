<?php


namespace App\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        dd($request);
        $locale = 'ru';
        $request->setLocale($locale);
    }

    static public function getSubscribedEvents()
    {
        return [
          KernelEvents::REQUEST => [['onKernelRequest', 20]]
        ];
    }
}