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
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/list-view', name: 'product_list_view', methods: ['GET'])]
    public function productListTemplate(Request $request): Response
    {
        $page = max(0, $request->query->getInt('page'));
        $cantPages = ceil(count($this->productRepository->findAll()) / ProductRepository::CANT_MAX_PRODUCTS);
        $offset = $page * ProductRepository::CANT_MAX_PRODUCTS;
        $products = $this->productRepository->findAllPaginator($offset);

        return $this->render('ProductTemplates/productList.html.twig', [
            'products' => $products,
            'previous' => $page - 1,
            'next' =>  $page + 1,
            'cantMaxima' => ProductRepository::CANT_MAX_PRODUCTS,
            'cantPages' => $cantPages
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/list-view/user', name: 'product_list_view_user', methods: ['GET'])]
    public function productListTemplateWithUser(Request $request): Response
    {
        $page = max(0, $request->query->getInt('page'));
        $cantPages = ceil(count($this->productRepository->findAll()) / ProductRepository::CANT_MAX_PRODUCTS);
        $offset = $page * ProductRepository::CANT_MAX_PRODUCTS;
        $products = $this->productRepository->findAllPaginator($offset);

        return $this->render('ProductTemplates/productListUser.html.twig', [
            'products' => $products,
            'previous' => $page - 1,
            'next' =>  $page + 1,
            'cantMaxima' => ProductRepository::CANT_MAX_PRODUCTS,
            'cantPages' => $cantPages
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/expired/list-view', name: 'product_expired_list_view', methods: ['GET'])]
    public function productExpiredListTemplate(Request $request): Response
    {
        $page = max(0, $request->query->getInt('page'));
        $cantPages = ceil($this->productRepository->countExpiredProducts() / ProductRepository::CANT_MAX_PRODUCTS);
        $offset = $page * ProductRepository::CANT_MAX_PRODUCTS;
        $products = $this->productRepository->findExpiredProducts($offset);

        return $this->render('ProductTemplates/productList.html.twig', [
            'products' => $products,
            'previous' => $page - 1,
            'next' =>  $page + 1,
            'cantMaxima' => ProductRepository::CANT_MAX_PRODUCTS,
            'cantPages' => $cantPages,
            'expired' => true
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/details/{code}', name: 'product_details', methods: ['GET'])]
    public function productDetails(string $code): RedirectResponse|Response
    {
        $product = $this->productRepository->findByCode($code);
        $action = '';
        $message = '';

        if (!empty($_GET['mnsj'])) {
            $action = $_GET['mnsj'] == 'ok' ? 'exito' : 'error';
            $message = $_GET['mnsj'] == 'ok' ? 'Se ha agregado con éxito' : 'Ha ocurrido un error';
        }

        $productJson = $this->serializer->serialize($product, 'json');
        $productInvoice = $this->serializer->deserialize($productJson, ProductInvoice::class, 'json');
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
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/add', name: 'add_product')]
    public function addProduct(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, ['method' => 'POST']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setName(ucfirst($product->getName()));
            $this->productManager->addProduct($product);
            $this->documentManager->flush();

            return $this->redirect('/product/details/' . $product->getCode());
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
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/update/{code}', name: 'update_product')]
    public function updateProduct(string $code, Request $request): Response
    {
        $product = $this->productRepository->findByCode($code);
        $form = $this->createForm(UpdateProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setName(ucfirst($product->getName()));
            $this->productManager->updateProduct($product);
            $this->documentManager->flush();

            return $this->redirect('/product/details/' . $code);
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
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/{code}', name: 'delete_product')]
    public function deleteProduct(string $code, Request $request): RedirectResponse|Response
    {
        $product = $this->productRepository->findByCode($code);
        $form = $this->createForm(DeleteProductType::class, $product);
        $form->handleRequest($request);

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
