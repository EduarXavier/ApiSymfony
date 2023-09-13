<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\Product;
use App\Document\ProductInvoice;
use App\Form\DeleteProductType;
use App\Form\ProductShoppingCartType;
use App\Form\ProductType;
use App\Form\UpdateProductType;
use App\Repository\ProductRepository;
use App\Managers\ProductManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\Attribute\Required;

#[Route('/product')]
class ProductController extends AbstractController
{
    private ProductRepository $productRepository;
    private ProductManager $productManager;
    private DocumentManager $documentManager;
    private SerializerInterface $serializer;

    #[Required]
    public function setProductRepository(ProductRepository $productRepository): void
    {
        $this->productRepository = $productRepository;
    }

    #[Required]
    public function setProductManager(ProductManager $productManager): void
    {
        $this->productManager = $productManager;
    }

    #[Required]
    public function setDocumentManager(DocumentManager $documentManager): void
    {
        $this->documentManager = $documentManager;
    }

    #[Required]
    public function setSerializerInterface(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
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

        $productJson = $this->serializer->serialize($product, "json");
        $productInvoice = $this->serializer->deserialize($productJson, ProductInvoice::class, "json");
        $productInvoice->setAmount(1);
        $formAddShoppingCart = $this->createForm(ProductShoppingCartType::class, $productInvoice);

        return $this->render('ProductTemplates/productDetails.html.twig', [
            'product' => $product,
            $action => $message,
            'formAddShoppingCart' => $formAddShoppingCart
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
            $this->productManager->addProduct($product);
            $this->documentManager->flush();

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
            $this->productManager->updateProduct($product);
            $this->documentManager->flush();

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
            $this->productManager->deleteProduct($product);
            $this->documentManager->flush();

            return $this->redirect('/product/list-view');
        }

        return $this->render('ProductTemplates/productForms.html.twig', [
            'form' => $form,
            'name' => 'Eliminar producto',
            'option' => 'Eliminar',
        ]);
    }
}
