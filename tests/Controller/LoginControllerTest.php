<?php

namespace App\Tests\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LoginControllerTest extends WebTestCase
{
    private static ?KernelBrowser $client;

    public function testLoginView(): void
    {
        self::$client->request(
            'GET',
            'http://gasolapp/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );

        self::assertSelectorTextContains('h2', 'Login');
        self::assertEquals('http://gasolapp/login-view', self::$client->getCrawler()->getUri());
        self::assertEquals(Response::HTTP_OK, self::$client->getResponse()->getStatusCode());
        self::assertFalse(self::$client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    public function testLoginAuthenticate(): void
    {
        self::$client->request(
            'GET',
            'http://gasolapp/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );
        $crawler = self::$client->submitForm('login', [
            '_username' => 'personaFalsa@gmail.com',
            '_password' => 'claveSegura',
        ]);

        self::assertEquals('http://gasolapp/', $crawler->getUri());
        self::assertEquals(Response::HTTP_OK, self::$client->getResponse()->getStatusCode());
        self::assertTrue(self::$client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    public function testLogout(): void
    {
        self::$client->request('GET', 'http://gasolapp/logout');

        self::assertEquals('http://gasolapp/login-view', self::$client->getCrawler()->getUri());
        self::assertEquals(Response::HTTP_OK, self::$client->getResponse()->getStatusCode());
        self::assertFalse(self::$client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    public function testLoginAuthenticateWithBadCredentials(): void
    {
        self::$client->request(
            'GET',
            'http://gasolapp/login-view',
            [
                '_username' => '',
                '_password' => ''
            ]
        );
        $crawler = self::$client->submitForm('login', [
            '_username' => 'personaFalsaLogin@gmail.com',
            '_password' => 'claveNoSegura',
        ]);

        self::assertEquals('http://gasolapp/login-view', $crawler->getUri());
        self::assertEquals(Response::HTTP_OK, self::$client->getResponse()->getStatusCode());
        self::assertFalse(self::$client->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN'));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::$client = static::createClient();
        self::$client->followRedirects();
        self::$client->request(
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
        self::$client = null;
        self::getContainer()->get(DocumentManager::class)->getSchemaManager()->dropDatabases();
    }
}
