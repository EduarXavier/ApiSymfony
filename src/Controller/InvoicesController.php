<?php

namespace App\Controller;

use App\Document\Invoice;
use App\Form\FactureType;
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

    /**
     * @throws Exception
     */
    #[Route("/shopping-cart", name: "shopping_cart", methods : ["POST"])]
    public function shoppingCart(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $data = json_decode($request->getContent(), true,);
        $invoices = new Invoice();

        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->submit($request->request->get($form->getName()));

        //$form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
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

    #[Route("/shopping-cart", name: "update_shopping_cart", methods : ["PATCH"])]
    public function updateShoppingCart(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $data = json_decode($request->getContent(), true,);
        $invoices = new Invoice();

        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->submit($request->request->get($form->getName()));

        if ($form->isSubmitted() && $form->isValid())
        {
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
    public  function createInvoices(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $data = (object) json_decode($request->getContent(), true,);
        $invoice = new Invoice();

        $form = $this->createForm(FactureType::class, $invoice);
        $form->submit($request->request->get($form->getName()));

        if($form->isSubmitted() && $form->isValid())
        {
            $document = $data->document;
            $invoice = $this->invoicesRepository->findByDocumentAndStatus($document, "shopping-cart", $documentManager);

            if ($invoice)
            {
                $this->invoicesRepository->createInvoice($invoice, $documentManager);

                return new JsonResponse(["mensaje" => "Se ha creado la factura"], 200);
            }
            else
            {
                return new JsonResponse(["error" => "No se ha encontrado la lista de productos"], 400);
            }
        }
        else
        {
            return new JsonResponse(["error" => "Ha ocurrido un error con los datos enviados"], 400);
        }
    }

    /**
     * @throws MongoDBException
     */
    #[Route("/pay-invoice", name: "pay-invoice", methods: ["GET"])]
    public  function payInvoice(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $data = (object) json_decode($request->getContent(), true,);
        $invoice = new Invoice();

        $form = $this->createForm(ShoppingCartType::class, $invoice);
        $form->submit($request->request->get($form->getName()));

        if($form->isSubmitted() && $form->isValid())
        {
            $id = $data->id;
            $invoice = $this->invoicesRepository->findById($id, $documentManager);

            if ($invoice)
            {
                $this->invoicesRepository->payInvoice($invoice, $documentManager);

                return new JsonResponse(["mensaje" => "Se ha pagado"], 200);
            }
            else
            {
                return new JsonResponse(["error" => "No se ha encontrado la factura"], 400);
            }
        }
        else
        {
            return new JsonResponse(["error" => "Ha ocurrido un error con los datos enviados"], 400);
        }
    }

    #[Route("/list", name : "invoices_list")]
    public function findAllInvoices(): RedirectResponse|Response
    {
        session_abort();
        session_start();

        if(!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN")
        {
            $invoices = $this->invoicesRepository->findAll($this->documentManager);

            return $this->render("InvoiceTemplates/invoiceList.html.twig", [
                "invoices" => $invoices
            ]);
        }

        return $this->redirectToRoute("login_template");

    }

    #[Route("/details/{id}", name : "invoices_details")]
    public function invoiceDetails(string $id): RedirectResponse|Response
    {
        session_abort();
        session_start();

        if(!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN")
        {
            $invoice = $this->invoicesRepository->findById($id ,$this->documentManager);

            return $this->render("InvoiceTemplates/invoiceDetails.html.twig", [
                "invoice" => $invoice
            ]);
        }

        return $this->redirectToRoute("login_template");

    }

    #[Route("/add/shopping-cart", name : "add_product_shopping-cart")]
    public function AddShoppingCart(Request $request): RedirectResponse
    {
        $invoices = new Invoice();

        $form = $this->createForm(ShoppingCartType::class, $invoices);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $validation = $this->invoicesRepository->AddProductsToshoppingCart
            (
                $invoices->getProducts(),
                $invoices->getUserDocument(),
                $this->documentManager
            );

            return $validation ?
                $this->redirect("/shopping-cart/details")
                :
                $this->redirectToRoute("add_product_shopping-cart");
        }

        return $this->redirectToRoute("login_template");

    }

}
