<?php

namespace App\Controller;

use App\Document\Product;
use App\Document\User;
use App\Form\DeleteProductType;
use App\Form\LoginType;
use App\Form\ProductType;
use App\Form\UpdateProductType;
use App\Repository\ProductRepository;
use App\Repository\ProductRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
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

    #[Route("/list-view", name: "product_list", methods : ["GET"])]
    public function productListTemplate(): Response
    {
        session_abort();
        session_start();
        $products = $this->productRepository->findAll($this->documentManager);

        if(!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN")
        {
            return $this->render("ProductTemplates/productList.html.twig", [
                "products" => $products,
            ]);
        }

        return $this->redirectToRoute('login_template');
    }

    #[Route("/details/{id}", name: "product_details", methods : ["GET"])]
    public function productDetails(string $id): RedirectResponse|Response
    {
        session_abort();
        session_start();
        $product = $this->productRepository->findById($id, $this->documentManager);

        if(!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN")
        {
            $accion = "";
            $mensaje = "";

            if(!empty($_GET["mnsj"]))
            {
                $accion = $_GET["mnsj"] == "ok" ? "exito" : "error";
                $mensaje = $_GET["mnsj"] == "ok" ?
                    "Se ha agregado con éxito"
                    :
                    "Ha ocurrido un error";
            }

            return $this->render("ProductTemplates/productDetails.html.twig", [
                "product" => $product,
                $accion => $mensaje
            ]);
        }

        return $this->redirectToRoute('login_template');
    }

    #[Route("/add", name: "add_product")]
    public function addProduct(Request $request): Response
    {
        session_abort();
        session_start();
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, ['method' => 'POST']);
        $form->handleRequest($request);

        if(!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN")
        {
            if($form->isSubmitted() && $form->isValid())
            {
                $id = $this->productRepository->addProduct($product, $this->documentManager);

                if($id)
                {
                    return $this->redirect("/product/details/$id");
                }
                else
                {
                    $this->addFlash("error", "No se ha agregado el producto: $id");
                    $this->redirectToRoute("add_product");
                }
            }
        }
        else if ($_SESSION["rol"] != "ADMIN")
        {
            return $this->redirectToRoute("login_template");
        }

        return $this->render("ProductTemplates/productForms.html.twig", [
            "form" => $form,
            "name" => "Crear producto",
            "option" => "Crear"
        ]);
    }

    #[Route("/update/{id}", name: "update_product")]
    public function updateProduct(string $id, Request $request): Response
    {
        session_abort();
        session_start();
        $product = $this->productRepository->findById($id, $this->documentManager);
        $form = $this->createForm(UpdateProductType::class, $product);
        $form->handleRequest($request);

        if(!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN")
        {
            if($form->isSubmitted() && $form->isValid())
            {
                $id = $this->productRepository->updateProduct($product, $this->documentManager);

                if($id)
                {
                    return $this->redirect("/product/details/$id");
                }
                else
                {
                    $this->addFlash("error", "No se ha actualizado el producto: $id");
                    $this->redirectToRoute("update_product");
                }
            }
        }
        else if ($_SESSION["rol"] != "ADMIN")
        {
            return $this->redirectToRoute("login_template");
        }

        return $this->render("ProductTemplates/productForms.html.twig", [
            "form" => $form,
            "name" => "Actualizar producto",
            "option" => "Actualizar"
        ]);
    }

    #[Route("/delete/{id}", name: "delete_product")]
    public function deleteProduct(string $id, Request $request): RedirectResponse|Response
    {
        session_abort();
        session_start();
        $product = $this->productRepository->findById($id, $this->documentManager);
        $form= $this->createForm(DeleteProductType::class, $product);
        $form->handleRequest($request);

        if(!empty($_SESSION["user"]) && !empty($_SESSION["rol"]) && $_SESSION["rol"] == "ADMIN")
        {
            if($form->isSubmitted() && $form->isValid())
            {
                $response = $this->productRepository->deleteProduct($product, $this->documentManager);

                if($response)
                {
                    return $this->redirect("/product/list-view");
                }
                else
                {
                    $this->addFlash("error", "No se ha actualizado el producto: $id");
                    $this->redirectToRoute("delete_product");
                }
            }

            return $this->render("ProductTemplates/productForms.html.twig", [
                "form" => $form,
                "name" => "Eliminar producto",
                "option" => "Eliminar"
            ]);
        }

        return $this->redirectToRoute('login_template');
    }
}
