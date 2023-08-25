<?php

namespace App\Controller;

use App\Document\Product;
use App\Repository\ProductRepository;
use App\Repository\ProductRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/product")]
class ProductController extends AbstractController
{

    private ProductRepositoryInterface $productRepository;
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        $this->productRepository = new ProductRepository();
    }

    #[Route("/list", name: "product_list", methods : ["GET"])]
    public function productList(DocumentManager $documentManager): ?JsonResponse
    {
        $products = $this->productRepository->findAll($this->documentManager);

        return $this->json($products, 200);
    }

}