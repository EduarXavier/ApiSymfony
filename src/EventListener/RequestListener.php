<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->isMethod('POST')) {
            if(count($request->request) == 0) {
                $content = $request->getContent() ?? null;
                if($content != null) {
                    $request->request->replace(json_decode($content, true));
                }
            }
        }
    }
}
