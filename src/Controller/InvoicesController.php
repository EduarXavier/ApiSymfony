<?php

namespace App\Controller;

use App\Document\Invoice;
use App\Form\ShoppingCartType;
use App\Repository\InvoicesRepository;
use App\Repository\InvoicesRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/invoices", name: "shopping_cart", methods : ["POST"])]
class InvoicesController extends AbstractController
{
    private InvoicesRepositoryInterface $invoicesRepository;
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        $this->invoicesRepository = new InvoicesRepository();
    }

    #[Route("/shopping-cart", name: "shopping_cart", methods : ["POST"])]
    public function shoppingCart(Request $request, DocumentManager $documentManager): ?JsonResponse
    {
        $data = json_decode($request->getContent(), true,);
        $invoices = new Invoice();

        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->submit($request->request->get($form->getName()));

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

    #[Route("/create-invoice", name: "create-invoice", methods: ["POST"])]
    public  function createInvoices(Request $request, DocumentManager $documentManager): ?JsonResponse
    {

    }
}