<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\Invoice;
use App\Document\Product;
use App\Form\FactureType;
use App\Form\PayInvoiceType;
use App\Form\ProductShoppingCartType;
use App\Form\ShoppingCartType;
use App\Repository\InvoicesRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Services\EmailService;
use App\Services\InvoiceService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/invoices")]
class InvoicesController extends AbstractController
{
    private InvoicesRepository $invoicesRepository;
    private UserRepository $userRepository;
    private ProductRepository $productRepository;
    private EmailService $emailService;
    private InvoiceService $invoiceService;

    public function __construct(
        InvoiceService $invoicesService,
        EmailService $emailService,
        UserRepository $userRepository,
        InvoicesRepository $invoicesRepository,
        ProductRepository $productRepository
    ) {
        $this->invoiceService = $invoicesService;
        $this->emailService = $emailService;
        $this->userRepository = $userRepository;
        $this->invoicesRepository = $invoicesRepository;
        $this->productRepository = $productRepository;
    }

    // Endpoints API

    /**
     * @throws Exception
     */
    #[Route("/shopping-cart", name: "shopping_cart", methods: ["POST"])]
    public function shoppingCart(Request $request): ?JsonResponse
    {
        $invoices = new Invoice();
        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(["error" => "Ha ocurrido un error"], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findByDocument($invoices->getUser()->getDocument());
        $invoices->setUser($user);
        $validation = $this->invoicesRepository->addProductsToShoppingCart(
            $invoices->getProducts(),
            $invoices->getUser()
        );

        return $validation ?
            new JsonResponse(["mensaje" => "Agregado con éxito"], Response::HTTP_OK) :
            new JsonResponse(["error" => "No se han podido agregar los productos"], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route("/update/shopping-cart/", name: "update_shopping_cart", methods: ["POST"])]
    public function updateShoppingCart(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $invoices = new Invoice();
        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(["error" => "Ha ocurrido un error"], Response::HTTP_BAD_REQUEST);
        }

        $validation = $this->invoicesRepository->updateShoppingCart(
            $invoices->getProducts(),
            $invoices->getUser()
        );

        return $validation ?
            new JsonResponse(["mensaje" => "Agregado con éxito"], Response::HTTP_OK) :
            new JsonResponse(["error" => "No se han podido agregar los productos"], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws MongoDBException
     */
    #[Route("/create-invoice", name: "create-invoice", methods: ["POST"])]
    public function createInvoices(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $invoice = new Invoice();
        $form = $this->createForm(FactureType::class, $invoice);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(["error" => "Ha ocurrido un error con los datos enviados"], Response::HTTP_BAD_REQUEST);
        }

        $document = $invoice->getUser()->getDocument();
        $invoice = $this->invoicesRepository->findByDocumentAndStatus($document, "shopping-cart");

        if (!$invoice) {
            return new JsonResponse(["error" => "No se ha encontrado la lista de productos"], Response::HTTP_BAD_REQUEST);
        }
        $this->invoicesRepository->createInvoice($invoice);

        return new JsonResponse(["mensaje" => "Se ha creado la factura"], Response::HTTP_OK);
    }

    /**
     * @throws MongoDBException
     * @throws TransportExceptionInterface
     */
    #[Route("/pay-invoice", name: "pay-invoice", methods: ["POST"])]
    public function payInvoice(Request $request): ?JsonResponse
    {
        $invoice = new Invoice();
        $form = $this->createForm(PayInvoiceType::class, $invoice);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(["error" => "Ha ocurrido un error con los datos enviados"], Response::HTTP_BAD_REQUEST);
        }

        $invoice = $this->invoicesRepository->findById($invoice->getId(), "invoice");

        if (!$invoice) {
            return new JsonResponse(["error" => "No se ha encontrado la factura"], Response::HTTP_BAD_REQUEST);
        }

        $invoiceEmail = $this->invoicesRepository->findByDocumentAndStatus(
            $invoice->getUserDocument(),
            "pay",
        );

        if ($invoiceEmail == null) {
            $this->emailService->sendEmail($invoice->getUser()->getEmail(), "first-shop");
        }

        $this->invoicesRepository->payInvoice($invoice);

        return new JsonResponse(["mensaje" => "Se ha pagado"], Response::HTTP_OK);
    }

    // Endpoints View

    #[Route("/list", name: "invoices_list")]
    public function findAllInvoices(Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $invoices = $this->invoicesRepository->findAllByUser($session->get("user"));

        return $this->render("InvoiceTemplates/invoiceList.html.twig", [
            "invoices" => $invoices
        ]);
    }

    #[Route("/list/{status}", name: "invoices_list_status")]
    public function findAllInvoicesForStatus(Request $request, string $status): RedirectResponse|Response
    {
        $session = $request->getSession();

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $invoices = $this->invoicesRepository->findAllForStatus($session->get("user"), $status);

        return $this->render("InvoiceTemplates/invoiceList.html.twig", [
            "invoices" => $invoices
        ]);
    }

    #[Route("/shopping-cart/list", name: "shopping_cart_list")]
    public function shoppingCartList(Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus(
            $session->get("document"),
            "shopping-cart"
        );

        return $this->render("InvoiceTemplates/shoppingCartDetails.html.twig", [
            "shoppingCart" => $shoppingCart
        ]);
    }

    #[Route("/details/{id}", name: "invoices_details")]
    public function invoiceDetails(Request $request, string $id): RedirectResponse|Response
    {
        $session = $request->getSession();

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $invoice = $this->invoicesRepository->findById($id, "invoice");

        return $this->render("InvoiceTemplates/invoiceDetails.html.twig", [
            "invoice" => $invoice
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route("/shopping-cart/add-product", name: "add_product_shopping_cart", methods: ["POST"])]
    public function addProductShoppingCart(Request $request): RedirectResponse
    {
        $session = $request->getSession();
        $product = new Product();
        $form = $this->createForm(ProductShoppingCartType::class, $product);

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirect("/product/details/" . $product->getId() . "?mnsj=err");
        }

        $products = new ArrayCollection();
        $amount  = $product->getAmount();
        $product = clone $product;
        $product->setAmount($amount);
        $products->add($product);

        $this->invoicesRepository->addProductsToShoppingCart(
            $products,
            $session->get("user"),
        );

        return $this->redirect("/product/details/" . $product->getCode() . "?mnsj=ok");
    }

    /**
     * @throws MongoDBException
     */
    #[Route("/create/invoice/", name: "create_invoice_view")]
    public function createInvoice(Request $request): RedirectResponse
    {
        $session = $request->getSession();
        $invoice = new Invoice();
        $form = $this->createForm(FactureType::class, $invoice);

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirect("/invoices/list");
        }

        $invoice = $this->invoicesRepository->findByCode($invoice->getCode(), "shopping-cart");

        $this->invoicesRepository->createInvoice($invoice);

        return $this->redirect("/invoices/details/" . $invoice->getId());
    }

    /**
     * @throws MongoDBException
     * @throws TransportExceptionInterface
     */
    #[Route("/pay/{id}", name: "pay_invoice_view")]
    public function payInvoiceView(Request $request, string $id): RedirectResponse
    {
        $session = $request->getSession();

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $invoice = $this->invoicesRepository->findById($id, "invoice");

        if (!$invoice) {
            return $this->redirect("/invoices/details/" . $id);
        }

        $invoiceEmail = $this->invoicesRepository->findAllForStatus(
            $invoice->getUser(),
            "pay"
        );

        if ($invoiceEmail == null) {
            $user = $this->userRepository->findByDocument(
                $invoice->getUser()->getDocument()
            );
            $this->emailService->sendEmail($user->getEmail(), "first-shop");
        }

        $this->invoicesRepository->payInvoice($invoice);

        return $this->redirect("/invoices/details/" . $invoice->getId());
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route("/delete/invoice/{id}", name: "delete_invoice_view")]
    public function deleteInvoiceView(Request $request, string $id): RedirectResponse
    {
        $session = $request->getSession();

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $invoice = $this->invoicesRepository->findById($id, "invoice");
        $this->invoicesRepository->deleteInvoice($invoice);

        return $this->redirect("/invoices/details/" . $invoice->getId());
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route("/shopping-cart/delete/{document}", name: "delete_shopping_cart_view")]
    public function deleteShoppingCartView(Request $request, string $document): RedirectResponse
    {
        $session = $request->getSession();

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus(
            $document,
            "shopping-cart"
        );
        $this->invoicesRepository->deleteShoppingCart($shoppingCart);

        return $this->redirect("/invoices/details/" . $shoppingCart->getId());
    }

    /**
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @throws MongoDBException
     */
    #[Route("/shopping-cart/delete-product/{id}", name: "delete_product_to_shopping_cart_view")]
    public function deleteProductToShoppingCartView(Request $request, string $id): RedirectResponse
    {
        $session = $request->getSession();

        if (empty($session->get("user")) || empty($session->get("rol")) || $session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        $this->invoicesRepository->deleteProductToShoppingCart(
            $session->get("user"),
            $id
        );

        return $this->redirect("/invoices/shopping-cart/list");
    }
}
