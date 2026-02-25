<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RegistrationModeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%env(APP_MODE)%')]
        private readonly string $appMode,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 190],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        if ('/api/register' !== $request->getPathInfo()) {
            return;
        }

        if ('self_hosted' !== $this->appMode) {
            return;
        }

        $event->setResponse(new JsonResponse(
            ['code' => 'registration_disabled', 'message' => 'Public registration is not available.'],
            Response::HTTP_FORBIDDEN,
        ));
    }
}
