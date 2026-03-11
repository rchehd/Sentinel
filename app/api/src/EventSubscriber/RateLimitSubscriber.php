<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.login')]
        private readonly RateLimiterFactory $loginLimiter,
        #[Autowire(service: 'limiter.register')]
        private readonly RateLimiterFactory $registerLimiter,
        #[Autowire(service: 'limiter.activate')]
        private readonly RateLimiterFactory $activateLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 50],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $method = $request->getMethod();
        $ip = $request->getClientIp() ?? 'unknown';

        $limiter = match (true) {
            'POST' === $method && '/api/login' === $path => $this->loginLimiter->create($ip),
            'POST' === $method && '/api/register' === $path => $this->registerLimiter->create($ip),
            'GET' === $method && str_starts_with($path, '/api/activate/') => $this->activateLimiter->create($ip),
            default => null,
        };

        if (null === $limiter) {
            return;
        }

        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $event->setResponse(new JsonResponse(
                ['code' => 'too_many_requests', 'error' => 'Too many requests. Please try again later.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $limit->getRetryAfter()->getTimestamp() - time()],
            ));
        }
    }
}
