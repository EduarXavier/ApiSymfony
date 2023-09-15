<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\Invoice;
use App\Document\ProductInvoice;
use App\Form\FactureType;
use App\Form\ProductShoppingCartType;
use App\Form\ShoppingCartType;
use App\Managers\InvoiceManager;
use App\Repository\InvoicesRepository;
use App\Repository\UserRepository;
use App\Services\EmailService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Service\Attribute\Required;

#[Route('/invoices')]
class InvoiceController extends AbstractController
{
    private UserRepository $userRepository;
    private InvoicesRepository $invoicesRepository;
    private EmailService $emailService;
    private InvoiceManager $invoiceManager;
    private DocumentManager $documentManager;
    private Security $security;

    #[Required]
    public function setUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    #[Required]
    public function setInvoiceRepository(InvoicesRepository $invoicesRepository): void
    {
        $this->invoicesRepository = $invoicesRepository;
    }

    #[Required]
    public function setEmailService(EmailService $emailService): void
    {
        $this->emailService = $emailService;
    }

    #[Required]
    public function setInvoiceManager(InvoiceManager $invoiceManager): void
    {
        $this->invoiceManager = $invoiceManager;
    }

    #[Required]
    public function setDocumentManager(DocumentManager $documentManager): void
    {
        $this->documentManager = $documentManager;
    }

    #[Required]
    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    // Endpoints API

    /**
     * @throws Exception
     */
    #[Route('/api/shopping-cart', name: 'shopping_cart', methods: ['POST'])]
    public function shoppingCart(Request $request): ?JsonResponse
    {
        $invoices = new Invoice();
        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(['error' => 'Ha ocurrido un error'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findByDocument($invoices->getUser()->getDocument());

        if (!$this->invoiceManager->addProductsToShoppingCart($invoices->getProducts(), $user)) {
            return new JsonResponse(['error' => 'No se han podido agregar los productos'], Response::HTTP_BAD_REQUEST);
        }

        $this->documentManager->flush();

        return new JsonResponse(['mensaje' => 'Agregado con éxito'], Response::HTTP_OK);
    }

    /**
     * @throws MongoDBException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    #[Route('/api/update/shopping-cart/', name: 'update_shopping_cart', methods: ['POST'])]
    public function updateShoppingCart(Request $request): ?JsonResponse
    {
        $invoices = new Invoice();
        $form = $this->createForm(ShoppingCartType::class, $invoices);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(['error' => 'Ha ocurrido un error'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findByDocument($invoices->getUser()->getDocument());
        $invoice = $this->invoicesRepository->findByUserAndStatus($user, Invoice::SHOPPINGCART);

        if (!$invoice) {
            return new JsonResponse(['error' => 'No se ha encontrado el carrito'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->invoiceManager->addToExistingCart($invoices->getProducts(), $invoice)) {
            return new JsonResponse(['error' => 'No se han podido agregar los productos'], Response::HTTP_BAD_REQUEST);
        }

        $this->documentManager->flush();

        return new JsonResponse(['mensaje' => 'Agregado con éxito'], Response::HTTP_OK);
    }

    /**
     * @throws MongoDBException
     */
    #[Route('/api/create-invoice', name: 'create-invoice', methods: ['POST'])]
    public function createInvoices(Request $request): ?JsonResponse
    {
        $invoice = new Invoice();
        $form = $this->createForm(FactureType::class, $invoice);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(['error' => 'Ha ocurrido un error con los datos enviados'], Response::HTTP_BAD_REQUEST);
        }

        $invoice = $this->invoicesRepository->findByCode($invoice->getCode());

        if (!$invoice) {
            return new JsonResponse(['error' => 'No se ha encontrado la factura'], Response::HTTP_BAD_REQUEST);
        }

        $this->invoiceManager->createInvoice($invoice);
        $this->documentManager->flush();

        return new JsonResponse(['mensaje' => 'Se ha creado la factura'], Response::HTTP_OK);
    }

    /**
     * @throws MongoDBException
     * @throws TransportExceptionInterface
     */
    #[Route('/api/pay-invoice', name: 'pay-invoice', methods: ['POST'])]
    public function payInvoice(Request $request): ?JsonResponse
    {
        $invoice = new Invoice();
        $form = $this->createForm(FactureType::class, $invoice);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(['error' => 'Ha ocurrido un error con los datos enviados'], Response::HTTP_BAD_REQUEST);
        }

        $invoice = $this->invoicesRepository->findByCode($invoice->getCode());

        if (!$invoice) {
            return new JsonResponse(['error' => 'No se ha encontrado la factura'], Response::HTTP_BAD_REQUEST);
        }

        $invoiceEmail = $this->invoicesRepository->findByUserAndStatus(
            $invoice->getUser(),
            Invoice::PAY,
        );

        if ($invoiceEmail == null) {
            $this->emailService->sendEmail($invoice->getUser(), 'first-shop');
        }

        $this->invoiceManager->payInvoice($invoice);
        $this->documentManager->flush();

        return new JsonResponse(['mensaje' => 'Se ha pagado'], Response::HTTP_OK);
    }

    // Endpoints View

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/list', name: 'invoices_list')]
    public function findAllInvoices(): RedirectResponse|Response
    {
        $user = $this->security->getUser();
        $user = $this->userRepository->findByEmail($user->getUserIdentifier());
        $invoices = $this->invoicesRepository->findAllByUser($user);

        return $this->render('InvoiceTemplates/invoiceList.html.twig', [
            'invoices' => $invoices
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/resume', name: 'invoices_resume')]
    public function resume(): RedirectResponse|Response
    {
        $user = $this->security->getUser();
        $user = $this->userRepository->findByEmail($user->getUserIdentifier());
        $products = $this->invoiceManager->invoiceResume($user, null);

        return $this->render('InvoiceTemplates/invoiceResume.html.twig', [
            'products' => $products,
            'user' => $user
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/resume/{status}', name: 'invoices_resume_status')]
    public function resumeStatus(string $status): RedirectResponse|Response
    {
        $user = $this->security->getUser();
        $user = $this->userRepository->findByEmail($user->getUserIdentifier());
        $products = $this->invoiceManager->invoiceResume($user, $status);

        return $this->render('InvoiceTemplates/invoiceResume.html.twig', [
            'products' => $products,
            'user' => $user
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/list/{status}', name: 'invoices_list_status')]
    public function findAllInvoicesForStatus(string $status): RedirectResponse|Response
    {
        $user = $this->security->getUser();
        $user = $this->userRepository->findByEmail($user->getUserIdentifier());
        $invoices = $this->invoicesRepository->findAllForStatus($user, $status);

        return $this->render('InvoiceTemplates/invoiceList.html.twig', [
            'invoices' => $invoices
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/shopping-cart/list', name: 'shopping_cart_list')]
    public function shoppingCartList(): RedirectResponse|Response
    {
        $user = $this->security->getUser();
        $user = $this->userRepository->findByEmail($user->getUserIdentifier());
        $shoppingCart = $this->invoicesRepository->findByUserAndStatus(
            $user,
            Invoice::SHOPPINGCART
        );
        $formCreateInvoice = $this->createForm(FactureType::class, $shoppingCart);

        return $this->render('InvoiceTemplates/shoppingCartDetails.html.twig', [
            'shoppingCart' => $shoppingCart,
            'total' => $shoppingCart?->getTotal(),
            'formCreateInvoice' => $formCreateInvoice
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/details/{id}', name: 'invoices_details')]
    public function invoiceDetails(string $id): RedirectResponse|Response
    {
        $invoice = $this->invoicesRepository->findByIdAndStatus($id, Invoice::INVOICE);
        $formCreateInvoice = $this->createForm(FactureType::class, $invoice);

        return $this->render('InvoiceTemplates/invoiceDetails.html.twig', [
            'invoice' => $invoice,
            'total' => $invoice?->getTotal(),
            'formCreateInvoice' => $formCreateInvoice
        ]);
    }

    /**
     * @throws Exception
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/shopping-cart/add-product', name: 'add_product_shopping_cart', methods: ['POST'])]
    public function addProductShoppingCart(Request $request): RedirectResponse
    {
        $product = new ProductInvoice();
        $form = $this->createForm(ProductShoppingCartType::class, $product);
        $form->handleRequest($request);
        $user = $this->security->getUser();
        $user = $this->userRepository->findByEmail($user->getUserIdentifier());

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirect('/product/details/' . $product->getCode() . '?mnsj=err');
        }

        $products = new ArrayCollection();
        $products->add($product);

        if (!$this->invoiceManager->addProductsToShoppingCart($products, $user)) {
            return $this->redirect('/product/details/' . $product->getCode() . '?mnsj=err');
        }

        $this->documentManager->flush();

        return $this->redirect('/product/details/' . $product->getCode() . '?mnsj=ok');
    }

    /**
     * @throws MongoDBException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/create/invoice/', name: 'create_invoice_view')]
    public function createInvoice(Request $request): RedirectResponse
    {
        $invoice = new Invoice();
        $form = $this->createForm(FactureType::class, $invoice);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirect('/invoices/list');
        }

        $invoice = $this->invoicesRepository->findByCode($invoice->getCode());
        $this->invoiceManager->createInvoice($invoice);
        $this->documentManager->flush();

        return $this->redirect('/invoices/details/' . $invoice->getId());
    }

    /**
     * @throws MongoDBException
     * @throws TransportExceptionInterface
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/pay/{id}', name: 'pay_invoice_view')]
    public function payInvoiceView(string $id): RedirectResponse
    {
        $invoice = $this->invoicesRepository->findByIdAndStatus($id, Invoice::INVOICE);

        if (!$invoice) {
            return $this->redirect('/invoices/details/' . $id);
        }

        $invoiceEmail = $this->invoicesRepository->findAllForStatus(
            $invoice->getUser(),
            Invoice::PAY
        );

        if ($invoiceEmail == null) {
            $user = $this->userRepository->findByDocument(
                $invoice->getUser()->getDocument()
            );
            $this->emailService->sendEmail($user, 'first-shop');
        }

        $this->invoiceManager->payInvoice($invoice);
        $this->documentManager->flush();

        return $this->redirect('/invoices/details/' . $invoice->getId());
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/delete/invoice/{id}', name: 'delete_invoice_view')]
    public function deleteInvoiceView(Request $request, string $id): RedirectResponse
    {
        $invoice = $this->invoicesRepository->findByIdAndStatus($id, Invoice::INVOICE);

        if (!$this->invoiceManager->cancelInvoice($invoice)) {
            $this->addFlash('error', 'No se ha podido cancelar');
            return $this->redirect('/invoices/details/' . $invoice->getId());
        }

        $this->documentManager->flush();

        return $this->redirect('/invoices/details/' . $invoice->getId());
    }

    /**
     * @throws MongoDBException
     * @throws MappingException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/shopping-cart/delete/{document}', name: 'delete_shopping_cart_view')]
    public function deleteShoppingCartView(string $document): RedirectResponse
    {
        $user = $this->userRepository->findByDocument($document);
        $shoppingCart = $this->invoicesRepository->findByUserAndStatus(
            $user,
            Invoice::SHOPPINGCART
        );

        if (!$this->invoiceManager->deleteShoppingCart($shoppingCart)) {
            $this->addFlash('error', 'No se ha podido eliminar');
            $this->redirect('/invoices/details/' . $shoppingCart->getId());
        }

        $this->documentManager->flush();

        return $this->redirect('/invoices/details/' . $shoppingCart->getId());
    }

    /**
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @throws MongoDBException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/shopping-cart/delete-product/{code}', name: 'delete_product_to_shopping_cart_view')]
    public function deleteProductToShoppingCartView(Request $request, string $code): RedirectResponse
    {
        $user = $this->security->getUser();
        $user = $this->userRepository->findByEmail($user->getUserIdentifier());
        $this->invoiceManager->deleteProductToShoppingCart($user, $code);
        $this->documentManager->flush();

        return $this->redirect('/invoices/shopping-cart/list');
    }
}
