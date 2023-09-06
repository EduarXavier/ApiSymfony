<?php

declare(strict_types=1);

namespace App\Controller;
use App\Document\Invoice;
use App\Form\ShoppingCartType;
use App\Services\InvoiceService;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/invoices")]
class InvoiceController extends AbstractController
{
    private InvoiceService $invoiceService;

    public function __construct(
        InvoiceService $invoicesService,

    ) {
        $this->invoiceService = $invoicesService;
    }


    // Endpoints API

    /**
     * @throws Exception
     */
    #[Route("/shopping-cart", name: "shopping_cart", methods: ["POST"])]
    public function shoppingCart(Request $request): ?JsonResponse
    {
        $shoppingCart = new Invoice();
        $form = $this->createForm(ShoppingCartType::class, $shoppingCart);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()){
           return $this->json(["error" => "Los datos no son correrctos: " . $form->getErrors()], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

}