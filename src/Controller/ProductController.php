<?php

namespace App\Controller;

use App\Document\Products;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/product")]
class ProductController extends AbstractController
{

    #[Route("/list", name: "product_list", methods : ["GET"])]
    public function productList(DocumentManager $documentManager): ?JsonResponse
    {
        $repository = $documentManager->getRepository(Products::class);
        $products = $repository->findAll();

        return $this->json($products, 200);
    }

}