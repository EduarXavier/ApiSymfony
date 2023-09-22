<?php

namespace App\Tests\Controller;

use App\Document\Invoice;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvoiceControllerTest extends WebTestCase
{
    private static ?object $documentManager;
    private ?KernelBrowser $client;
    private static array $header;
    private static bool $creation = false;
    private static string $codeProduct;
    private const BASE_URL = 'http://gasolapp';

    public function testShoppingCart(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($content->mensaje);
        self::assertEquals('Agregado con éxito', $content->mensaje);
    }

    public function testShoppingCartWithUserFalse(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1000"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($content->error);
        self::assertEquals('Usuario no encontrado', $content->error);
    }

    public function testShoppingCartWhitProductFalse(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => '65035c6f3b747-Product-Not-Existing"',
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($content->error);
        self::assertEquals('No se han podido agregar los productos', $content->error);
    }

    public function testShoppingCartUnauthorized(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
            ]
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertNotNull($content->message);
    }

    public function testUpdateShoppingCart(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/update/shopping-cart/',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($content->mensaje);
        self::assertEquals('Agregado con éxito', $content->mensaje);
    }

    public function testUpdateShoppingCartNotFound(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/update/shopping-cart/',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($content->error);
        self::assertEquals('No se ha encontrado el carrito', $content->error);
    }

    public function testUpdateShoppingCartWithUserNotFound(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/update/shopping-cart/',
            [
                "user" => [
                    "document" => "6002531"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($content->error);
        self::assertEquals('Usuario no encontrado', $content->error);
    }

    public function testUpdateShoppingCartWithProductNotFound(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/update/shopping-cart/',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => '65035c6f3b747-Product-Not-Existing',
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($content->error);
        self::assertEquals('No se han podido agregar los productos', $content->error);
    }

    public function testCreateInvoicesNotFound(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/create-invoice',
            [
                "code" => '650b1468f3368-1009090'
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($content->error);
        self::assertEquals('No se ha encontrado la factura', $content->error);
    }

    public function testCreateInvoices(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $code = json_decode($this->client->getResponse()->getContent())->code;
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/create-invoice',
            [
                "code" => $code
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($content->mensaje);
        self::assertEquals('Se ha creado la factura', $content->mensaje);
    }

    public function testPayInvoice(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $code = json_decode($this->client->getResponse()->getContent())->code;
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/create-invoice',
            [
                "code" => $code
            ],
            [],
            self::$header
        );
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/pay-invoice',
            [
                "code" => $code
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($content->mensaje);
        self::assertEquals('Se ha pagado', $content->mensaje);
    }

    public function testPayInvoiceNotFound(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/pay-invoice',
            [
                "code" => '650b1468f3368-1009090'
            ],
            [],
            self::$header
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent());

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNotNull($content->error);
        self::assertEquals('No se ha encontrado la factura', $content->error);
    }

    public function testFindAllInvoices(): void
    {
        $crawler = $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/list',
        );
        $response = $this->client->getResponse();
        $alert = $crawler->filter('div.alert');

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('Lista de pedidos');
        self::assertEquals(1, $alert->count());
        self::assertEquals('No se han encontrado pedidos.', trim($alert->text()));
    }

    public function testResume(): void
    {
        $crawler = $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/resume',
        );
        $response = $this->client->getResponse();
        $alert = $crawler->filter('div.alert.alert-info');

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('User test - resume');
        self::assertEquals(1, $alert->count());
        self::assertEquals('No hay facturas registradas.', trim($alert->text()));
    }

    public function testResumeStatus(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $crawler = $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/resume/amount',
        );
        $response = $this->client->getResponse();
        $table= $crawler->filter('table.table.w-100');
        $rows = $crawler->filter('tr');

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('User test - resume');
        self::assertEquals(1, $table->count());
        self::assertEquals(3, $rows->count());
    }

    public function testFindAllInvoicesForStatus(): void
    {
        $crawler = $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/list/invoice',
        );
        $response = $this->client->getResponse();
        $alert = $crawler->filter('div.alert');

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertPageTitleSame('Lista de pedidos');
        self::assertEquals(1, $alert->count());
        self::assertEquals('No se han encontrado pedidos.', trim($alert->text()));
    }

    public function testShoppingCartList(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/api/shopping-cart',
            [
                "user" => [
                    "document" => "1009090"
                ],
                "products" => [
                    [
                        "code" => self::$codeProduct,
                        "amount" => 1
                    ]
                ]
            ],
            [],
            self::$header
        );
        $crawler = $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/shopping-cart/list',
        );
        $response = $this->client->getResponse();
        $tittle = $crawler->filter('h2.pt-5');
        $statusElement = $crawler->filter('p#status');
        $status = str_replace('Estado: ', '', trim($statusElement->text()));

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertSelectorExists('nav');
        self::assertEquals('Detalles del carrito', trim($tittle->text()));
        self::assertEquals(1, $tittle->count());
        self::assertEquals(invoice::SHOPPING_CART, $status);
    }

    public function testAddProductShoppingCart(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/shopping-cart/add-product',
            [
                'code' => self::$codeProduct,
                'amount' => 1
            ]
        );

        $response = $this->client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(self::BASE_URL.'/product/details/'.self::$codeProduct.'?mnsj=ok', $this->client->getCrawler()->getUri());
    }

    public function testAddProductNotExistingToShoppingCart(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/shopping-cart/add-product',
            [
                'code' => '65035c6f3b747-Product-Not-Existing',
                'amount' => 1
            ]
        );

        $response = $this->client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(self::BASE_URL.'/product/details/65035c6f3b747-Product-Not-Existing?mnsj=err', $this->client->getCrawler()->getUri());
    }

    public function testCreateInvoiceView(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/shopping-cart/add-product',
            [
                'code' => self::$codeProduct,
                'amount' => 1
            ]
        );
        $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/shopping-cart/list'
        );
        $crawler = $this->client->submitForm('Generar pedido');
        $response = $this->client->getResponse();
        $statusElement = $crawler->filter('p#status');
        $status = str_replace('Estado: ', '', trim($statusElement->text()));

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(1, $statusElement->count());
        self::assertEquals(Invoice::INVOICE, $status);
    }

    public function testPayInvoiceView(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/shopping-cart/add-product',
            [
                'code' => self::$codeProduct,
                'amount' => 1
            ]
        );
        $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/shopping-cart/list'
        );
        $this->client->submitForm('Generar pedido');
        $crawler = $this->client->clickLink('Pagar');
        $response = $this->client->getResponse();
        $statusElement = $crawler->filter('p#status');
        $status = str_replace('Estado: ', '', trim($statusElement->text()));

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(1, $statusElement->count());
        self::assertEquals(Invoice::PAY, $status);
    }

    public function testDeleteInvoiceView(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/shopping-cart/add-product',
            [
                'code' => self::$codeProduct,
                'amount' => 1
            ]
        );
        $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/shopping-cart/list'
        );
        $this->client->submitForm('Generar pedido');
        $crawler = $this->client->clickLink('Cancelar');
        $response = $this->client->getResponse();
        $statusElement = $crawler->filter('p#status');
        $status = str_replace('Estado: ', '', trim($statusElement->text()));

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(1, $statusElement->count());
        self::assertEquals(Invoice::CANCEL, $status);
    }

    public function testDeleteShoppingCartView(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/shopping-cart/add-product',
            [
                'code' => self::$codeProduct,
                'amount' => 1
            ]
        );
        $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/shopping-cart/list'
        );
        $crawler = $this->client->clickLink('Eliminar carrito');
        $response = $this->client->getResponse();
        $alert = $crawler->filter('div.alert.alert-info');

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(1, $alert->count());
        self::assertEquals('Este carrito está vacío.', trim($alert->text()));
    }

    public function testDeleteProductToShoppingCartView(): void
    {
        $this->client->request(
            'POST',
            self::BASE_URL.'/invoices/shopping-cart/add-product',
            [
                'code' => self::$codeProduct,
                'amount' => 1
            ]
        );
        $this->client->request(
            'GET',
            self::BASE_URL.'/invoices/shopping-cart/list'
        );
        $crawler = $this->client->clickLink('Eliminar');
        $response = $this->client->getResponse();
        $alert = $crawler->filter('div.alert.alert-info');

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(1, $alert->count());
        self::assertEquals('Este carrito está vacío.', trim($alert->text()));
    }

    public static function tearDownAfterClass(): void
    {
        self::$documentManager->getSchemaManager()->dropDatabases();
    }

    protected function setUp(): void
    {
        $this->client = self::createClient();
        self::$documentManager = $this->client->getContainer()->get(DocumentManager::class);
        $this->client->followRedirects();
        $this->loginWeb();
        $this->cleanData();
        if (!self::$creation)
        {
            $this->token();
            $this->addProduct();
            self::$creation = true;
        }
    }

    private function cleanData(): void
    {
        self::$documentManager->getSchemaManager()->dropDocumentCollection(Invoice::class);
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

    private function loginWeb(): void
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
            '_username' => 'userTest@gmail.com',
            '_password' => 'claveSegura',
        ]);
    }

    private function addProduct(): void
    {
        $this->loginWeb();
        $this->client->request(
            'GET',
            self::BASE_URL.'/product/add',
            [
                'name' => '',
                'amount' => 0,
                'price' => 0
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
        $codeFilter = $crawler->filter('#codeProduct')->text();

        self::$codeProduct = str_replace('Code: ', '', $codeFilter);
    }
}