<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use App\EventListener\RequestListener;

class RequestListenerTest extends KernelTestCase
{
    private RequestListener $listener;

    public function testOnKernelRequestJsonContent(): void
    {
        $content = json_encode(['key' => 'value']);
        $request = Request::create('/ruta-falsa', 'POST', [], [], [], [], $content);
        $event = new RequestEvent(self::$kernel, $request, 1);

        $this->listener->onKernelRequest($event);

        self::assertIsArray($request->request->all());
        self::assertEquals($content, $request->getContent());
        self::assertEquals(['key' => 'value'], $request->request->all());
    }

    public function testOnKernelRequestNoJsonContent(): void
    {
        $request = Request::create('/ruta-falsa', 'POST', [], [], [], [], 'non-json-content');
        $event = new RequestEvent(self::$kernel, $request, 1);

        $this->listener->onKernelRequest($event);

        self::assertIsArray($request->request->all());
        self::assertEquals('non-json-content', $request->getContent());
        self::assertEquals([], $request->request->all());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->listener = $container->get(RequestListener::class);
    }

    protected function tearDown(): void
    {
        unset($this->listener);
    }
}