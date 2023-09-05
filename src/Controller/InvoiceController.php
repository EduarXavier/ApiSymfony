<?php

declare(strict_types=1);

namespace App\Controller;
use App\Document\Invoice;
use App\Services\InvoiceService;
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

        return null;
    }

}