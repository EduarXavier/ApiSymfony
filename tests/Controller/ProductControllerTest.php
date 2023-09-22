<?php

namespace App\Tests\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends WebTestCase
{
    private ?KernelBrowser $client;
    private static ?object $documentManager;
    private static bool $create = false;
    private const BASE_URL = 'http://gasolapp';

    public function testProductList(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/product/api/list',
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertNotNull(json_decode($response->getContent()));
        self::assertIsArray(json_decode($response->getContent()));
    }

    public function testProductListTemplate(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/product/list-view',
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('Lista de productos');
    }

    public function testProductListTemplateForUser()
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/logout'
        );
        $this->client->request(
            'GET',
            self::BASE_URL.'/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );
        $this->client->submitForm('login', [
            '_username' => 'viewProducts@gmail.com',
            '_password' => 'claveSegura',
        ]);
        $this->client->request(
            'GET',
            self::BASE_URL.'/product/api/list',
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertNotNull(json_decode($response->getContent()));
        self::assertIsArray(json_decode($response->getContent()));
    }

    public function testProductExpiredListTemplate(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/product/expired/list-view',
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('Lista de productos');
    }

    public function testProductDetailsWhenProductNotFound(): void
    {
        $crawler = $this->client->request(
            'GET',
            self::BASE_URL.'/product/details/650478611714d-Producto-falso',
        );
        $response = $this->client->getResponse();
        $alertInfoElement = $crawler->filter('div.alert.alert-info');

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('Not found - details');
        self::assertGreaterThan(0, $alertInfoElement->count());
        self::assertEquals('Este producto no está disponible.', trim($alertInfoElement->text()));
    }

    public function testAddProduct(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/product/add',
            [
                'name' => 'False product',
                'amount' => 100,
                'price' => 1500
            ]
        );
        $crawler = $this->client->submitForm(
            'btnSubmit',
            [
                'name' => 'False product',
                'amount' => 100,
                'price' => 1500
            ]
        );
        $response = $this->client->getResponse();
        $titleInfo = $crawler->filter('h5.card-title');
        $textInfo = $crawler->filter('p.card-text');

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertEquals('Nombre: False product', trim($titleInfo->text()));
        self::assertPageTitleSame('False product - details');
        self::assertEquals(1, $titleInfo->count());
        self::assertEquals(4, $textInfo->count());
    }

    public function testUpdateProduct(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/product/add',
            [
                'name' => 'False product',
                'amount' => 100,
                'price' => 1500
            ]
        );
        $this->client->submitForm(
            'btnSubmit',
            [
                'name' => 'False product',
                'amount' => 100,
                'price' => 1500
            ]
        );
        $this->client->clickLink('Actualizar');
        $crawler = $this->client->submitForm(
            'btnSubmit',
            [
                'name' => 'False product update',
                'amount' => 10,
                'price' => 150
            ]
        );
        $response = $this->client->getResponse();
        $titleInfo = $crawler->filter('h5.card-title');

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('False product update - details');
        self::assertEquals('Nombre: False product update', trim($titleInfo->text()));
    }

    public function testDeleteProduct(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/product/add',
            [
                'name' => 'False product',
                'amount' => 100,
                'price' => 1500
            ]
        );
        $this->client->submitForm(
            'btnSubmit',
            [
                'name' => 'False product',
                'amount' => 100,
                'price' => 1500
            ]
        );
        $this->client->clickLink('Eliminar');
        $this->client->submitForm('btnSubmit');
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('Lista de productos');
    }

    /**
     * @throws Exception
     */
    public static function tearDownAfterClass(): void
    {
        self::$documentManager->getSchemaManager()->dropDatabases();
    }

    protected function setUp(): void
    {
        $this->client = self::createClient();
        self::$documentManager = $this->client->getContainer()->get(DocumentManager::class);
        $this->client->followRedirects();
        if (!self::$create) {
            $this->createUser();
            self::$create = true;
        }
        $this->authenticate();
    }

    private function createUser(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/user/api/add',
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
            'POST',
            self::BASE_URL.'/user/api/add',
            [
                'name' => 'Persona viewProducts',
                'document' => '100253469',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3000',
                'email' => 'viewProductsUser@gmail.com',
                'password' => 'claveSegura'
            ]
        );
    }

    private function authenticate(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/login-view',
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
}