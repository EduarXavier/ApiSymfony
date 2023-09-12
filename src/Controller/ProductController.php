<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\Product;
use App\Form\DeleteProductType;
use App\Form\ProductType;
use App\Form\UpdateProductType;
use App\Repository\ProductRepository;
use App\Services\ProductService;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    private ProductRepository $productRepository;
    private ProductService $productService;

    public function __construct(ProductRepository $productRepository, ProductService $productService)
    {
        $this->productRepository = $productRepository;
        $this->productService = $productService;
    }

    //API

    #[Route('/api/list', name: 'product_list', methods: ['GET'])]
    public function productList(): ?JsonResponse
    {
        $products = $this->productRepository->findAll();

        return $this->json($products, Response::HTTP_OK);
    }

    //VIEW

    #[Route('/list-view', name: 'product_list_view', methods: ['GET'])]
    public function productListTemplate(Request $request): Response
    {
        $session = $request->getSession();
        $products = $this->productRepository->findAll();

        if (empty($session->get('user')) || empty($session->get('rol')) || $session->get('rol') != 'ADMIN') {
            return $this->redirectToRoute('login_template');
        }

        return $this->render('ProductTemplates/productList.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/details/{code}', name: 'product_details', methods: ['GET'])]
    public function productDetails(Request $request, string $code): RedirectResponse|Response
    {
        $session = $request->getSession();
        $product = $this->productRepository->findByCode($code);
        $action = '';
        $message = '';

        if (empty($session->get('user')) || empty($session->get('rol')) || $session->get('rol') != 'ADMIN') {
            return $this->redirectToRoute('login_template');
        }

        if (!empty($_GET['mnsj'])) {
            $action = $_GET['mnsj'] == "ok" ? 'exito' : 'error';
            $message = $_GET['mnsj'] == "ok" ? 'Se ha agregado con éxito' : 'Ha ocurrido un error';
        }

        return $this->render('ProductTemplates/productDetails.html.twig', [
            'product' => $product,
            $action => $message,
        ]);
    }

    /**
     * @throws MongoDBException
     */
    #[Route('/add', name: 'add_product')]
    public function addProduct(Request $request): Response
    {
        $session = $request->getSession();
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, ['method' => 'POST']);
        $form->handleRequest($request);

        if (empty($session->get('user')) || empty($session->get('rol')) || $session->get('rol') != 'ADMIN') {
            return $this->redirectToRoute('login_template');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setName(ucfirst($product->getName()));
            $dm = $this->productService->addProduct($product);
            $dm->flush();

            return $this->redirect("/product/details/" . $product->getCode());
        }

        return $this->render('ProductTemplates/productForms.html.twig', [
            'form' => $form,
            'name' => 'Crear producto',
            'option' => 'Crear',
        ]);
    }

    /**
     * @throws MongoDBException
     * @throws LockException
     */
    #[Route('/update/{code}', name: 'update_product')]
    public function updateProduct(string $code, Request $request): Response
    {
        $session = $request->getSession();
        $product = $this->productRepository->findByCode($code);
        $form = $this->createForm(UpdateProductType::class, $product);
        $form->handleRequest($request);

        if (empty($session->get('user')) || empty($session->get('rol')) || $session->get('rol') != 'ADMIN') {
            return $this->redirectToRoute('login_template');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setName(ucfirst($product->getName()));
            $dm = $this->productService->updateProduct($product);
            $dm->flush();

            return $this->redirect("/product/details/$code");
        }

        return $this->render('ProductTemplates/productForms.html.twig', [
            'form' => $form,
            'name' => 'Actualizar producto',
            'option' => 'Actualizar',
        ]);
    }

    /**
     * @throws MongoDBException
     * @throws LockException
     */
    #[Route('/delete/{code}', name: 'delete_product')]
    public function deleteProduct(string $code, Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();
        $product = $this->productRepository->findByCode($code);
        $form = $this->createForm(DeleteProductType::class, $product);
        $form->handleRequest($request);

        if (empty($session->get('user')) || empty($session->get('rol')) || $session->get('rol') != 'ADMIN') {
            return $this->redirectToRoute('login_template');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $dm = $this->productService->deleteProduct($product);
            $dm->flush();

            return $this->redirect('/product/list-view');
        }

        return $this->render('ProductTemplates/productForms.html.twig', [
            'form' => $form,
            'name' => 'Eliminar producto',
            'option' => 'Eliminar',
        ]);
    }
}
