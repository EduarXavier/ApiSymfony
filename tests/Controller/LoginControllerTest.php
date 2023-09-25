<?php

namespace App\Tests\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LoginControllerTest extends WebTestCase
{
    private ?KernelBrowser $client;
    private static ?object $documentManager;
    private static bool $create = false;
    private const BASE_URL = 'http://gasolapp';

    public function testLoginApi(): void
    {
        $jsonData = json_encode([
            'username' => 'personaFalsa@gmail.com',
            'password' => 'claveSegura'
        ]);
        $this->client->request(
            'POST',
            self::BASE_URL.'/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonData
        );
        $response = $this->client->getResponse();
        $token = json_decode($response->getContent())->token;

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($token);
    }

    public function testLoginApiWithBadCredentials(): void
    {
        $jsonData = json_encode([
            'username' => 'personaNoFalsa@gmail.com',
            'password' => 'claveSegura'
        ]);
        $this->client->request(
            'POST',
            self::BASE_URL.'/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonData
        );
        $response = $this->client->getResponse();
        $message = json_decode($response->getContent())->message;

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertNotNull($message, 'Invalid credentials.');
    }

    public function testLoginView(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );

        self::assertSelectorTextContains('h2', 'Login');
        self::assertEquals(self::BASE_URL.'/login-view', $this->client->getCrawler()->getUri());
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertFalse($this->client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    public function testLoginAuthenticate(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );
        $crawler = $this->client->submitForm('login', [
            '_username' => 'personaFalsa@gmail.com',
            '_password' => 'claveSegura',
        ]);

        self::assertEquals(self::BASE_URL.'/', $crawler->getUri());
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    public function testLogout(): void
    {
        $this->client->request('GET', self::BASE_URL.'/logout');

        self::assertEquals(self::BASE_URL.'/login-view', $this->client->getCrawler()->getUri());
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertFalse($this->client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    public function testLoginAuthenticateWithBadCredentials(): void
    {
        $this->client->request(
            'GET',
            self::BASE_URL.'/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );
        $crawler = $this->client->submitForm('login', [
            '_username' => 'personaFalsaLogin@gmail.com',
            '_password' => 'claveNoSegura',
        ]);

        self::assertEquals(self::BASE_URL.'/login-view', $crawler->getUri());
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertFalse($this->client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    /**
     * @throws Exception
     */
    public static function tearDownAfterClass(): void
    {
        self::$documentManager->getSchemaManager()->dropDatabases();
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::$documentManager = $this->client->getContainer()->get(DocumentManager::class);
        $this->client->followRedirects();
        if (!self::$create) {
            $this->createUser();
            self::$create = true;
        }
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
                'email' => 'personaFalsa@gmail.com',
                'password' => 'claveSegura'
            ]
        );
    }
}
