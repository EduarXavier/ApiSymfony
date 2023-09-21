<?php

namespace App\Tests\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private static bool $create = false;
    private static array $header;
    private static object $user;
    private ?KernelBrowser $client;
    private static ?object $documentManager;
    private const BASE_URL = 'http://gasolapp';

    public function testAddUser(): void
    {
        $this->client->request(
            'POST',
            '/user/api/add',
            [
                'name' => 'Persona falsa AddUser',
                'document' => '1005545421',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3000',
                'email' => 'personaFalsaAddUser@gmail.com',
                'password' => 'claveSegura'
            ]
        );
        $response = $this->client->getResponse();
        $user = json_decode($response->getContent())->user;

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals('Persona falsa AddUser', $user->name);
        self::assertNotEquals('claveSegura', $user->password);
    }

    public function testAddUserWithIncompleteData(): void
    {
        $this->client->request(
            'POST',
            '/user/api/add',
            [
                'name' => 'Persona falsa',
                'rol' => 'ROLE_ADMIN',
                'phone' => '3000',
                'password' => 'claveSegura'
            ]
        );

        $response = $this->client->getResponse();
        $contentRequest = json_decode($this->client->getResponse()->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($contentRequest->error);
    }

    public function testAddUserWithAnExistingEmail(): void
    {
        $this->client->request(
            'POST',
            '/user/api/add',
            [
                'name' => 'Persona menos falsa',
                'document' => '1009878',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3001',
                'email' => 'userTest@gmail.com',
                'password' => 'claveSegura'
            ]
        );
        $response = $this->client->getResponse();
        $contentRequest = json_decode($this->client->getResponse()->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($contentRequest->error);
    }

    public function testAddUserWithAnExistingDocument(): void
    {
        $this->client->request(
            'POST',
            '/user/api/add',
            [
                'name' => 'Persona menos falsa',
                'document' => '1009090',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3001',
                'email' => 'personaMenosFalsa@gmail.com',
                'password' => 'claveSegura'
            ]
        );
        $response = $this->client->getResponse();
        $contentRequest = json_decode($this->client->getResponse()->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($contentRequest->error);
    }

    public function testUpdateUser(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/user/api/update/'.self::$user->id,
            [
                'address' => 'Calle falsa 2',
                'phone' => '4000',
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull(json_decode($response->getContent())->message);
    }

    public function testUpdateUserNotToken(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/user/api/update/6500881c739319887c0003c5',
            [
                'address' => 'Calle falsa 2',
                'phone' => '4000',
            ],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
            ]
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertNotNull(json_decode($response->getContent())->message);
    }

    public function testUpdateUserNotFound(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/user/api/update/6500881c739319887c0003c5',
            [
                'address' => 'Calle falsa 2',
                'phone' => '4000',
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull(json_decode($response->getContent())->error);
    }

    public function testUpdatePasswordUser(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/user/api/update/password/'.self::$user->id,
            [
                'password' => 'Clave segura 2'
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull(json_decode($response->getContent())->message);
    }

    public function testUpdatePasswordUserNotfound(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/user/api/update/password/6500881c739319887c0003c5',
            [
                'password' => 'Clave segura 2'
            ],
            [],
            self::$header            
        );
        $response = $this->client->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull(json_decode($response->getContent())->error);
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
        $this->client = self::createClient();
        self::$documentManager = $this->client->getContainer()->get(DocumentManager::class);
        $this->client->followRedirects();
        if (!self::$create) {
            $this->token();
            self::$create = true;
        }
    }

    private function token(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/user/api/add',
            [
                'name' => 'User test',
                'document' => '1009090',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3000',
                'email' => 'userTest@gmail.com',
                'password' => 'claveSegura'
            ]
        );
        self::$user = json_decode($this->client->getResponse()->getContent())->user;
        $jsonData = json_encode([
            "username" => "userTest@gmail.com",
            "password" => "claveSegura"
        ]);
        $this->client->request(
            'POST',
            self::BASE_URL.'/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonData
        );
        $token = json_decode($this->client->getResponse()->getContent())->token;
        self::$header = [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer '.$token
        ];
    }
}
