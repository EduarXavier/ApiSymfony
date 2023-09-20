<?php

namespace App\Tests\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private string $token;
    private ?KernelBrowser $client;
    private static ?object $documentManager;

    public function testAddUser(): void
    {
        $content = [
            'name' => 'Persona falsa AddUser',
            'document' => '1005545421',
            'rol' => 'ROLE_ADMIN',
            'address' => 'calle falsa',
            'phone' => '3000',
            'email' => 'personaFalsaAddUser@gmail.com',
            'password' => 'claveSegura'
        ];

        $this->client->request(
            'POST',
            '/user/api/add',
            $content
        );

        $response = $this->client->getResponse();
        $user = (array) json_decode($response->getContent())->user;

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals($content['name'], $user['name']);
        self::assertNotEquals($content['password'], $user['password']);
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
                'name' => 'Persona falsa',
                'document' => '100090',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3000',
                'email' => 'personaFalsa@gmail.com',
                'password' => 'claveSegura'
            ]
        );
        $this->client->request(
            'POST',
            '/user/api/add',
            [
                'name' => 'Persona menos falsa',
                'document' => '1009878',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3001',
                'email' => 'personaFalsa@gmail.com',
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
                'name' => 'Persona falsa',
                'document' => '100090',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3000',
                'email' => 'personaFalsa@gmail.com',
                'password' => 'claveSegura'
            ]
        );
        $this->client->request(
            'POST',
            '/user/api/add',
            [
                'name' => 'Persona menos falsa',
                'document' => '100090',
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
            'http://gasolapp/user/api/add',
            [
                'name' => 'Persona update',
                'document' => '109283',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3000',
                'email' => 'personaUpdate@gmail.com',
                'password' => 'claveSegura'
            ]
        );
        $user = json_decode($this->client->getResponse()->getContent())->user;
        $this->client->request(
            'POST',
            'http://gasolapp/user/api/update/'.$user->id,
            [
                'address' => 'Calle falsa 2',
                'phone' => '4000',
            ],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => 'Bearer '.$this->token
            ]
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
            'http://gasolapp/user/api/update/6500881c739319887c0003c5',
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
            'http://gasolapp/user/api/update/6500881c739319887c0003c5',
            [
                'address' => 'Calle falsa 2',
                'phone' => '4000',
            ],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => 'Bearer '.$this->token
            ]
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
            'http://gasolapp/user/api/add',
            [
                'name' => 'Person update',
                'document' => '1034265',
                'rol' => 'ROLE_ADMIN',
                'address' => 'calle falsa',
                'phone' => '3000',
                'email' => 'personUpdate@gmail.com',
                'password' => 'claveSegura'
            ]
        );
        $user = json_decode($this->client->getResponse()->getContent())->user;
        $this->client->request(
            'POST',
            'http://gasolapp/user/api/update/password/'.$user->id,
            [
                'password' => 'Clave segura 2'
            ],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => 'Bearer '.$this->token
            ]
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
            'http://gasolapp/user/api/update/password/6500881c739319887c0003c5',
            [
                'password' => 'Clave segura 2'
            ],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => 'Bearer '.$this->token
            ]
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
        $this->token();
    }

    private function token(): void
    {
        $this->client->request(
            'POST',
            'http://gasolapp/user/api/add',
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
        $jsonData = json_encode([
            "username" => "userTest@gmail.com",
            "password" => "claveSegura"
        ]);
        $this->client->request(
            'POST',
            'http://gasolapp/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonData
        );
        $this->token = json_decode($this->client->getResponse()->getContent())->token;
    }
}
