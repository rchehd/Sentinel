<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\SetupWizardSubscriber;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SetupWizardSubscriberTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeRepo(int $count): UserRepository
    {
        $repo = $this->createStub(UserRepository::class);
        $repo->method('count')->willReturn($count);

        return $repo;
    }

    private function makeEvent(string $path, string $method = 'GET', bool $isMain = true): RequestEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = Request::create($path, $method);
        $type = $isMain ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST;

        return new RequestEvent($kernel, $request, $type);
    }

    // -------------------------------------------------------------------------
    // Sub-requests
    // -------------------------------------------------------------------------

    public function testSubRequestIsIgnored(): void
    {
        $event = $this->makeEvent('/api/login', 'GET', false);
        (new SetupWizardSubscriber($this->makeRepo(0)))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    // -------------------------------------------------------------------------
    // Always-allowed paths
    // -------------------------------------------------------------------------

    public function testHealthCheckPassesThroughWithNoUsers(): void
    {
        $event = $this->makeEvent('/api/health');
        (new SetupWizardSubscriber($this->makeRepo(0)))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOptionsRequestPassesThroughWithNoUsers(): void
    {
        $event = $this->makeEvent('/api/login', 'OPTIONS');
        (new SetupWizardSubscriber($this->makeRepo(0)))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    // -------------------------------------------------------------------------
    // Unconfigured state (no users)
    // -------------------------------------------------------------------------

    public function testNonSetupRouteReturns503WhenNoUsers(): void
    {
        $event = $this->makeEvent('/api/login');
        (new SetupWizardSubscriber($this->makeRepo(0)))->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        $this->assertSame('/setup/admin', $data['redirect']);
    }

    public function testSetupAdminRoutePassesThroughWhenNoUsers(): void
    {
        $event = $this->makeEvent('/api/setup/admin');
        (new SetupWizardSubscriber($this->makeRepo(0)))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testSetupStatusRoutePassesThroughWhenNoUsers(): void
    {
        $event = $this->makeEvent('/api/setup/status');
        (new SetupWizardSubscriber($this->makeRepo(0)))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    // -------------------------------------------------------------------------
    // Configured state (users exist)
    // -------------------------------------------------------------------------

    public function testSetupAdminRouteReturns403WhenUsersExist(): void
    {
        $event = $this->makeEvent('/api/setup/admin');
        (new SetupWizardSubscriber($this->makeRepo(1)))->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testSetupStatusRoutePassesThroughWhenUsersExist(): void
    {
        $event = $this->makeEvent('/api/setup/status');
        (new SetupWizardSubscriber($this->makeRepo(1)))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testLoginRoutePassesThroughWhenUsersExist(): void
    {
        $event = $this->makeEvent('/api/login');
        (new SetupWizardSubscriber($this->makeRepo(1)))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    // -------------------------------------------------------------------------
    // Subscribed events
    // -------------------------------------------------------------------------

    public function testSubscribesToKernelRequest(): void
    {
        $events = SetupWizardSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('kernel.request', $events);
    }

    public function testKernelRequestPriorityIs200(): void
    {
        $events = SetupWizardSubscriber::getSubscribedEvents();

        // Events can be registered as [method, priority]
        $registration = $events['kernel.request'];
        $priority = \is_array($registration) ? $registration[1] : 0;

        $this->assertSame(200, $priority);
    }
}
