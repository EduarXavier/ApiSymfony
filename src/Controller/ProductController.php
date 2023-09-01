<?php

namespace App\Controller;

use App\Document\Product;
use App\Form\DeleteProductType;
use App\Form\ProductType;
use App\Form\UpdateProductType;
use App\Repository\ProductRepository;
use App\Repository\ProductRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function productList(): ?JsonResponse
    {
        $products = $this->productRepository->findAll($this->documentManager);

        return $this->json($products, 200);
    }

    #[Route("/list-view", name: "product_list_view", methods : ["GET"])]
    public function productListTemplate(Request $request): Response
    {
        $session = $request->getSession();
        $products = $this->productRepository->findAll($this->documentManager);

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            return $this->render("ProductTemplates/productList.html.twig", [
                "products" => $products,
            ]);
        }

        return $this->redirectToRoute('login_template');
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    #[Route("/details/{id}", name: "product_details", methods : ["GET"])]
    public function productDetails(Request $request, string $id): RedirectResponse|Response
    {
        $session = $request->getSession();
        $product = $this->productRepository->findById($id, $this->documentManager);

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            $action = "";
            $message = "";

            if (!empty($_GET["mnsj"])) {
                if ($_GET["mnsj"] == "ok"){
                    $action = "exito";
                    $message = "Se ha agregado con éxito";
                }
                else{
                    $action = "error";
                    $message = "Ha ocurrido un error";
                }
            }

            return $this->render("ProductTemplates/productDetails.html.twig", [
                "product" => $product,
                $action => $message,
            ]);
        }

        return $this->redirectToRoute('login_template');
    }

    /**
     * @throws MongoDBException
     */
    #[Route("/add", name: "add_product")]
    public function addProduct(Request $request): Response
    {
        $session = $request->getSession();
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, ['method' => 'POST']);
        $form->handleRequest($request);

        if(!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            if ($form->isSubmitted() && $form->isValid()) {
                $id = $this->productRepository->addProduct($product, $this->documentManager);

                if ($id) {
                    return $this->redirect("/product/details/$id");
                }
                else {
                    $this->addFlash("error", "No se ha agregado el producto: $id");
                    $this->redirectToRoute("add_product");
                }
            }
        }
        else if ($session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        return $this->render("ProductTemplates/productForms.html.twig", [
            "form" => $form,
            "name" => "Crear producto",
            "option" => "Crear",
        ]);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     * @throws LockException
     */
    #[Route("/update/{id}", name: "update_product")]
    public function updateProduct(string $id, Request $request): Response
    {
        $session = $request->getSession();
        $product = $this->productRepository->findById($id, $this->documentManager);
        $form = $this->createForm(UpdateProductType::class, $product);
        $form->handleRequest($request);

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            if ($form->isSubmitted() && $form->isValid()) {
                $id = $this->productRepository->updateProduct($product, $this->documentManager);

                if($id) {
                    return $this->redirect("/product/details/$id");
                }
                else {
                    $this->addFlash("error", "No se ha actualizado el producto: $id");
                    $this->redirectToRoute("update_product");
                }
            }
        }
        else if ($session->get("rol") != "ADMIN") {
            return $this->redirectToRoute("login_template");
        }

        return $this->render("ProductTemplates/productForms.html.twig", [
            "form" => $form,
            "name" => "Actualizar producto",
            "option" => "Actualizar",
        ]);
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     * @throws LockException
     */
    #[Route("/delete/{id}", name: "delete_product")]
    public function deleteProduct(string $id, Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();
        $product = $this->productRepository->findById($id, $this->documentManager);
        $form= $this->createForm(DeleteProductType::class, $product);
        $form->handleRequest($request);

        if (!empty($session->get("user")) && !empty($session->get("rol")) && $session->get("rol") == "ADMIN") {
            if($form->isSubmitted() && $form->isValid()) {
                $response = $this->productRepository->deleteProduct($product, $this->documentManager);

                if ($response) {
                    return $this->redirect("/product/list-view");
                }
                else {
                    $this->addFlash("error", "No se ha actualizado el producto: $id");
                    $this->redirectToRoute("delete_product");
                }
            }

            return $this->render("ProductTemplates/productForms.html.twig", [
                "form" => $form,
                "name" => "Eliminar producto",
                "option" => "Eliminar",
            ]);
        }

        return $this->redirectToRoute('login_template');
    }
}
