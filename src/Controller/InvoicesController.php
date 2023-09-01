<?php

namespace App\Controller;

use App\Document\Invoice;
use App\Document\Product;
use App\Form\FactureType;
use App\Form\PayInvoiceType;
use App\Form\ProductShoppingCartType;
use App\Form\ShoppingCartType;
use App\Repository\InvoicesRepository;
use App\Repository\InvoicesRepositoryInterface;
use App\Repository\UserRepository;
use App\Repository\UserRepositoryInterface;
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
    private InvoicesRepositoryInterface $invoicesRepository;
    private UserRepositoryInterface $userRepository;
    private DocumentManager $documentManager;
    private EmailController $emailController;

    public function __construct(DocumentManager $documentManager, EmailController $emailController)
    {
        $this->documentManager = $documentManager;
        $this->emailController = $emailController;
        $this->userRepository = new UserRepository();
        $this->invoicesRepository = new InvoicesRepository();
    }

    // Endpoints API

    /**
     * @throws Exception
     */
    #[Route("/shopping-cart", name: "shopping_cart", methods: ["POST"])]
    public function shoppingCart(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $invoices = new Invoice();
        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $validation = $this->invoicesRepository->addProductsToShoppingCart(
                $invoices->getProducts(),
                $invoices->getUserDocument(),
                $this->documentManager
            );

            return $validation ?
                new JsonResponse(["mensaje" => "Agregado con éxito"], 200) :
                new JsonResponse(["error" => "No se han podido agregar los productos"], 400);
        }

        return new JsonResponse(["error" => "Ha ocurrido un error"], 400);
    }

    #[Route("/update/shopping-cart/", name: "update_shopping_cart", methods: ["POST"])]
    public function updateShoppingCart(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $invoices = new Invoice();
        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $validation = $this->invoicesRepository->updateShoppingCart(
                $invoices->getProducts(),
                $invoices->getUserDocument(),
                $this->documentManager
            );

            return $validation ?
                new JsonResponse(["mensaje" => "Agregado con éxito"], 200) :
                new JsonResponse(["error" => "No se han podido agregar los productos"], 400);
        }

        return new JsonResponse(["error" => "Ha ocurrido un error"], 400);
    }

    #[Route("/create-invoice", name: "create-invoice", methods: ["POST"])]
    public function createInvoices(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $invoice = new Invoice();
        $form = $this->createForm(FactureType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $document = $invoice->getUserDocument();
            $invoice = $this->invoicesRepository->findByDocumentAndStatus($document, "shopping-cart", $documentManager);

            if ($invoice) {
                $this->invoicesRepository->createInvoice($invoice, $documentManager);

                return new JsonResponse(["mensaje" => "Se ha creado la factura"], 200);
            } else {
                return new JsonResponse(["error" => "No se ha encontrado la lista de productos"], 400);
            }
        } else {
            return new JsonResponse(["error" => "Ha ocurrido un error con los datos enviados"], 400);
        }
    }

    /**
     * @throws MongoDBException
     * @throws TransportExceptionInterface
     */
    #[Route("/pay-invoice", name: "pay-invoice", methods: ["POST"])]
    public function payInvoice(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $invoice = new Invoice();
        $form = $this->createForm(PayInvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $id = $invoice->getId();
            $invoice = $this->invoicesRepository->findById($id, $documentManager);

            if ($invoice) {
                $invoiceEmail = $this->invoicesRepository->findByDocumentAndStatus(
                    $invoice->getUserDocument(),
                    "pay",
                    $documentManager
                );

                if ($invoiceEmail == null) {
                    $user = $this->userRepository->findByDocument(
                        $invoice->getUserDocument(),
                        $documentManager
                    );
                    $this->emailController->sendEmail($user->getEmail(), "first-shop");
                }

                $this->invoicesRepository->payInvoice($invoice, $documentManager);

                return new JsonResponse(["mensaje" => "Se ha pagado"], 200);
            } else {
                return new JsonResponse(["error" => "No se ha encontrado la factura"], 400);
            }
        } else {
            return new JsonResponse(["error" => "Ha ocurrido un error con los datos enviados"], 400);
        }
    }

    // Endpoints View

    #[Route("/list", name: "invoices_list")]
    public function findAllInvoices(Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $invoices = $this->invoicesRepository->findAll($this->documentManager);

            return $this->render("InvoiceTemplates/invoiceList.html.twig", [
                "invoices" => $invoices
            ]);
        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/shopping-cart/list", name: "shopping_cart_list")]
    public function shoppingCartList(Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus(
                $session->get("document"),
                "shopping-cart",
                $this->documentManager
            );

            return $this->render("InvoiceTemplates/shoppingCartDetails.html.twig", [
                "shoppingCart" => $shoppingCart
            ]);
        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/details/{id}", name: "invoices_details")]
    public function invoiceDetails(Request $request, string $id): RedirectResponse|Response
    {
        $session = $request->getSession();

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $invoice = $this->invoicesRepository->findById($id, $this->documentManager);

            return $this->render("InvoiceTemplates/invoiceDetails.html.twig", [
                "invoice" => $invoice
            ]);
        }

        return $this->redirectToRoute("login_template");
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

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $products = $session->get("shopping-cart");
                $products[] = $product;
                $session->set("shopping-cart", array());

                $this->invoicesRepository->addProductsToShoppingCart(
                    $products,
                    $session->get("document"),
                    $this->documentManager
                );

                return $this->redirect("/product/details/" . $product->getId() . "?mnsj=ok");
            }

            return $this->redirect("/product/details/" . $product->getId() . "?mnsj=err");
        }

        return $this->redirectToRoute("login_template");
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

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $invoice = $this->invoicesRepository->findByDocumentAndStatus(
                    $invoice->getUserDocument(),
                    "shopping-cart",
                    $this->documentManager
                );
                $this->invoicesRepository->createInvoice($invoice, $this->documentManager);

                return $this->redirect("/invoices/details/" . $invoice->getId());
            }

            return $this->redirect("/invoices/list");
        }

        return $this->redirectToRoute("login_template");
    }

    /**
     * @throws MongoDBException
     * @throws TransportExceptionInterface
     */
    #[Route("/pay/{id}", name: "pay_invoice_view")]
    public function payInvoiceView(Request $request, string $id): RedirectResponse
    {
        $session = $request->getSession();

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $invoice = $this->invoicesRepository->findById($id, $this->documentManager);

            if ($invoice) {
                $invoiceEmail = $this->invoicesRepository->findByDocumentAndStatus(
                    $invoice->getUserDocument(),
                    "pay",
                    $this->documentManager
                );

                if ($invoiceEmail?->getUserDocument()) {
                    $user = $this->userRepository->findByDocument(
                        $invoice->getUserDocument(),
                        $this->documentManager
                    );
                    $this->emailController->sendEmail($user->getEmail(), "registry");
                }

                $this->invoicesRepository->payInvoice($invoice, $this->documentManager);
            }

            return $this->redirect("/invoices/details/" . $invoice->getId());
        }

        return $this->redirectToRoute("login_template");
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route("/delete/invoice/{id}", name: "delete_invoice_view")]
    public function deleteInvoiceView(Request $request, string $id): RedirectResponse
    {
        $session = $request->getSession();

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $invoice = $this->invoicesRepository->findById($id, $this->documentManager);
            $this->invoicesRepository->deleteInvoice($invoice, $this->documentManager);

            return $this->redirect("/invoices/details/" . $invoice->getId());
        }

        return $this->redirectToRoute("login_template");
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[Route("/shopping-cart/delete/{document}", name: "delete_shopping_cart_view")]
    public function deleteShoppingCartView(Request $request, string $document): RedirectResponse
    {
        $session = $request->getSession();

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus(
                $document,
                "shopping-cart",
                $this->documentManager
            );
            $this->invoicesRepository->deleteShoppingCart($shoppingCart, $this->documentManager);

            return $this->redirect("/invoices/details/" . $shoppingCart->getId());
        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/shopping-cart/delete-product/{id}", name: "delete_product_to_shopping_cart_view")]
    public function deleteProductToShoppingCartView(Request $request, string $id): RedirectResponse
    {
        $session = $request->getSession();

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $this->invoicesRepository->deleteProductToShoppingCart(
                $session->get("document"),
                $id,
                $this->documentManager
            );

            return $this->redirect("/invoices/shopping-cart/list");
        }

        return $this->redirectToRoute("login_template");
    }
}
