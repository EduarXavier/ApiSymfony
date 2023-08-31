<?php

namespace App\Controller;

use App\Document\Invoice;
use App\Document\Product;
use App\Form\FactureType;
use App\Form\ProductShoppingCartType;
use App\Form\ShoppingCartType;
use App\Repository\InvoicesRepository;
use App\Repository\InvoicesRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/invoices")]
class InvoicesController extends AbstractController
{
    private InvoicesRepositoryInterface $invoicesRepository;
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        $this->invoicesRepository = new InvoicesRepository();
    }

    //Enpoints API

    /**
     * @throws Exception
     */
    #[Route("/shopping-cart", name: "shopping_cart", methods: ["POST"])]
    public function shoppingCart(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $data = json_decode($request->getContent(), true,);
        $invoices = new Invoice();

        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->submit($request->request->get($form->getName()));

        //$form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $validation = $this->invoicesRepository->AddProductsToshoppingCart
            (
                $data["products"],
                $data["userDocument"],
                $this->documentManager
            );

            return $validation ?
                new JsonResponse(["mensaje" => "Agregado con éxito"], 200)
                :
                new JsonResponse(["error" => "No se han podido agregar los productos"], 400);
        }

        return new JsonResponse(["error" => "Ha ocurrido un error"], 400);

    }

    #[Route("/shopping-cart", name: "update_shopping_cart", methods: ["PATCH"])]
    public function updateShoppingCart(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $data = json_decode($request->getContent(), true,);
        $invoices = new Invoice();

        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->submit($request->request->get($form->getName()));

        if ($form->isSubmitted() && $form->isValid()) {
            $validation = $this->invoicesRepository->updateShoppingCart
            (
                $data["products"],
                $data["userDocument"],
                $this->documentManager
            );

            return $validation ?
                new JsonResponse(["mensaje" => "Actualizado con éxito"], 200)
                :
                new JsonResponse(["error" => "No se han podido agregar los productos"], 400);
        }

        return new JsonResponse(["error" => "Ha ocurrido un error"], 400);

    }

    /**
     * @throws MongoDBException
     */
    #[Route("/create-invoice", name: "create-invoice", methods: ["POST"])]
    public function createInvoices(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $data = (object)json_decode($request->getContent(), true,);
        $invoice = new Invoice();

        $form = $this->createForm(FactureType::class, $invoice);
        $form->submit($request->request->get($form->getName()));

        if ($form->isSubmitted() && $form->isValid()) {
            $document = $data->document;
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
     */
    #[Route("/pay-invoice", name: "pay-invoice", methods: ["GET"])]
    public function payInvoice(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $data = (object)json_decode($request->getContent(), true,);
        $invoice = new Invoice();

        $form = $this->createForm(ShoppingCartType::class, $invoice);
        $form->submit($request->request->get($form->getName()));

        if ($form->isSubmitted() && $form->isValid()) {
            $id = $data->id;
            $invoice = $this->invoicesRepository->findById($id, $documentManager);

            if ($invoice) {
                $this->invoicesRepository->payInvoice($invoice, $documentManager);

                return new JsonResponse(["mensaje" => "Se ha pagado"], 200);
            } else {
                return new JsonResponse(["error" => "No se ha encontrado la factura"], 400);
            }
        } else {
            return new JsonResponse(["error" => "Ha ocurrido un error con los datos enviados"], 400);
        }
    }

    //Enpoints View

    #[Route("/list", name: "invoices_list")]
    public function findAllInvoices(): RedirectResponse|Response
    {
        session_abort();
        session_start();

        if (!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN") {
            $invoices = $this->invoicesRepository->findAll($this->documentManager);

            return $this->render("InvoiceTemplates/invoiceList.html.twig", [
                "invoices" => $invoices
            ]);
        }

        return $this->redirectToRoute("login_template");

    }

    #[Route("/shopping-cart/list", name: "shopping_cart_list")]
    public function shoppingCartList(): RedirectResponse|Response
    {
        session_abort();
        session_start();

        if (!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN") {
            $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus($_SESSION["document"], "shopping-cart" ,$this->documentManager);

            return $this->render("InvoiceTemplates/shoppingCartDetails.html.twig", [
                "shoppingCart" => $shoppingCart
            ]);
        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/details/{id}", name: "invoices_details")]
    public function invoiceDetails(string $id): RedirectResponse|Response
    {
        session_abort();
        session_start();

        if (!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN") {
            $invoice = $this->invoicesRepository->findById($id, $this->documentManager);

            return $this->render("InvoiceTemplates/invoiceDetails.html.twig", [
                "invoice" => $invoice
            ]);
        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/shopping-cart/add-product", name: "add_product_shopping_cart", methods: ["POST"])]
    public function addProductShoppingCart(Request $request): RedirectResponse
    {
        session_abort();
        session_start();

        $product = new Product();

        $form = $this->createForm(ProductShoppingCartType::class, $product);

        if (!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN") {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $products = $_SESSION["shopping-cart"];
                $products[] = [
                    "id" => $product->getId(),
                    "amount" => $product->getAmount()
                ];
                $_SESSION["shopping-cart"] = array();

                $this->invoicesRepository->AddProductsToshoppingCart
                (
                    $products,
                    $_SESSION["document"],
                    $this->documentManager
                );

                return $this->redirect("/product/details/" . $product->getId() . "?mnsj=ok");
            }

            return $this->redirect("/product/details/" . $product->getId() . "?mnsj=err");
        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/create/invoice/", name: "create_invoice_view")]
    public function createInvoice(Request $request): RedirectResponse
    {
        session_abort();
        session_start();

        $invoice = new Invoice();
        $form = $this->createForm(FactureType::class, $invoice);

        if (!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN") {

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $invoice = $this->invoicesRepository->findByDocumentAndStatus($invoice->getUserDocument(), "shopping-cart", $this->documentManager);
                $this->invoicesRepository->createInvoice($invoice, $this->documentManager);

                return $this->redirect("/invoices/details/". $invoice->getId());
            }

            return $this->redirect("/invoices/list");
        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/pay/{id}", name: "pay_invoice_view")]
    public function payInvoiceView(string $id): RedirectResponse
    {
        session_abort();
        session_start();

        if (!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN") {

            $invoice = $this->invoicesRepository->findById($id, $this->documentManager);
            $this->invoicesRepository->payInvoice($invoice, $this->documentManager);

            return $this->redirect("/invoices/details/". $invoice->getId());

        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/delete/invoice/{id}", name: "delete_invoice_view")]
    public function deleteInvoiceView(string $id): RedirectResponse
    {
        session_abort();
        session_start();

        if (!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN") {

            $invoice = $this->invoicesRepository->findById($id, $this->documentManager);
            $this->invoicesRepository->deleteInvoice($invoice, $this->documentManager);

            return $this->redirect("/invoices/details/". $invoice->getId());

        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/shopping-cart/delete/{document}", name: "delete_shopping_cart_view")]
    public function deleteShoppingCartView(string $document): RedirectResponse
    {
        session_abort();
        session_start();

        if (!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN") {

            $shoppingCart = $this->invoicesRepository->findByDocumentAndStatus($document, "shopping-cart" ,$this->documentManager);
            $this->invoicesRepository->deleteShoppingCart($shoppingCart, $this->documentManager);

            return $this->redirect("/invoices/details/". $shoppingCart->getId());

        }

        return $this->redirectToRoute("login_template");
    }

    #[Route("/shopping-cart/delete-product/{id}", name: "delete_product_to_shopping_cart_view")]
    public function deleteProductToShoppingCartView(string $id): RedirectResponse
    {
        session_abort();
        session_start();

        if (!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN") {

            $this->invoicesRepository->deleteProductToShoppingCart($_SESSION["document"], $id ,$this->documentManager);

            return $this->redirect("/invoices/shopping-cart/list");

        }

        return $this->redirectToRoute("login_template");
    }
}
