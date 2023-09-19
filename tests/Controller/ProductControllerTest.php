<?php

namespace App\Tests\Controller;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends WebTestCase
{
    private ?KernelBrowser $client;

    public function testProductList(): void
    {
        $this->client->request(
            'GET',
            'http://gasolapp/product/api/list',
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertIsArray(json_decode($response->getContent()));
    }

    public function testProductListTemplate(): void
    {
        $this->client->request(
            'GET',
            'http://gasolapp/product/list-view',
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('Lista de productos');
    }

    public function testProductExpiredListTemplate(): void
    {
        $this->client->request(
            'GET',
            'http://gasolapp/product/expired/list-view',
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('Lista de productos');
    }

    public function testProductDetails(): void
    {
        $crawler = $this->client->request(
            'GET',
            'http://gasolapp/product/details/650478611714d-Producto-falso',
        );
        $response = $this->client->getResponse();
        $alertInfoElement = $crawler->filter('div.alert.alert-info[role="alert"]');

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('Not found - details');
        self::assertGreaterThan(0, $alertInfoElement->count());
        self::assertEquals('Este producto no está disponible.', trim($alertInfoElement->text()));
    }

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->followRedirects();
        $this->client->request(
            'POST',
            'http://gasolapp/user/api/add',
            [
                'name' => 'Persona viewProducts',
                'document' => '1004523',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3000',
                'email' => 'viewProducts@gmail.com',
                'password' => 'claveSegura'
            ]
        );
        $this->client->request(
            'GET',
            'http://gasolapp/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );
        $this->client->submitForm('login', [
            '_username' => 'viewProducts@gmail.com',
            '_password' => 'claveSegura',
        ]);
    }

    /**
     * @throws Exception
     */
    public static function tearDownAfterClass(): void
    {
        self::$kernel->shutdown();
        self::getContainer()->get(DocumentManager::class)->getSchemaManager()->dropDatabases();
    }
}