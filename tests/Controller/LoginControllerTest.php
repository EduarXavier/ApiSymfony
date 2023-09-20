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

    public function testLoginView(): void
    {
        $this->client->request(
            'GET',
            'http://gasolapp/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );

        self::assertSelectorTextContains('h2', 'Login');
        self::assertEquals('http://gasolapp/login-view', $this->client->getCrawler()->getUri());
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertFalse($this->client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    public function testLoginAuthenticate(): void
    {
        $this->client->request(
            'GET',
            'http://gasolapp/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );
        $crawler = $this->client->submitForm('login', [
            '_username' => 'personaFalsa@gmail.com',
            '_password' => 'claveSegura',
        ]);

        self::assertEquals('http://gasolapp/', $crawler->getUri());
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertTrue($this->client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    public function testLogout(): void
    {
        $this->client->request('GET', 'http://gasolapp/logout');

        self::assertEquals('http://gasolapp/login-view', $this->client->getCrawler()->getUri());
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertFalse($this->client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    public function testLoginAuthenticateWithBadCredentials(): void
    {
        $this->client->request(
            'GET',
            'http://gasolapp/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );
        $crawler = $this->client->submitForm('login', [
            '_username' => 'personaFalsaLogin@gmail.com',
            '_password' => 'claveNoSegura',
        ]);

        self::assertEquals('http://gasolapp/login-view', $crawler->getUri());
        self::assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertFalse($this->client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::$documentManager = $this->client->getContainer()->get(DocumentManager::class);
        $this->client->followRedirects();
        $this->client->request(
            'POST',
            'http://gasolapp/user/api/add',
            [
                'name' => 'Persona falsa',
                'document' => '100090',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3000',
                'email' => 'personaFalsa@gmail.com',
                'password' => 'claveSegura'
            ]
        );
    }

    /**
     * @throws Exception
     */
    public static function tearDownAfterClass(): void
    {
        self::$documentManager->getSchemaManager()->dropDatabases();
    }
}
