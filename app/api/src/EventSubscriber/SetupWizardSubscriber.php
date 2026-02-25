<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SetupWizardSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 200],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        // Always allow health checks and CORS preflight
        if ('/api/health' === $path || 'OPTIONS' === $event->getRequest()->getMethod()) {
            return;
        }

        try {
            $hasUsers = $this->userRepository->count() > 0;
        } catch (\Exception) {
            // DB not ready yet — pass through
            return;
        }

        $isSetupPath = str_starts_with($path, '/api/setup');

        if (!$hasUsers && !$isSetupPath) {
            $event->setResponse(new JsonResponse(
                ['redirect' => '/setup/admin'],
                Response::HTTP_SERVICE_UNAVAILABLE,
            ));

            return;
        }

        if ($hasUsers && $isSetupPath && '/api/setup/status' !== $path) {
            $event->setResponse(new JsonResponse(
                ['message' => 'Setup already completed.'],
                Response::HTTP_FORBIDDEN,
            ));
        }
    }
}
