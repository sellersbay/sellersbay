<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products')]
class ProductController extends AbstractController
{
    /**
     * @return User
     */
    private function getTypedUser(): User
    {
        /** @var User */
        return $this->getUser();
    }

    #[Route('/', name: 'app_products')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager
            ->getRepository(Product::class)
            ->findBy(['owner' => $this->getTypedUser()], ['updatedAt' => 'DESC']);

        return $this->render('product/index.html.twig', [
            'products' => $products
        ]);
    }

    #[Route('/new', name: 'app_product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $product->setOwner($this->getTypedUser());
        $product->setStatus('draft');

        if ($request->isMethod('POST')) {
            $product->setName($request->request->get('name'));
            $product->setDescription($request->request->get('description'));
            $product->setShortDescription($request->request->get('shortDescription'));
            
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully.');
            return $this->redirectToRoute('app_products');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit')]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Security check
        if ($product->getOwner() !== $this->getTypedUser()) {
            throw $this->createAccessDeniedException('You cannot edit this product.');
        }

        if ($request->isMethod('POST')) {
            $product->setName($request->request->get('name'));
            $product->setDescription($request->request->get('description'));
            $product->setShortDescription($request->request->get('shortDescription'));
            
            $entityManager->flush();

            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute('app_products');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Security check
        if ($product->getOwner() !== $this->getTypedUser()) {
            throw $this->createAccessDeniedException('You cannot delete this product.');
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute('app_products');
    }

    #[Route('/{id}/generate', name: 'app_product_generate_content')]
    public function generateContent(Product $product): Response
    {
        // Security check
        if ($product->getOwner() !== $this->getTypedUser()) {
            throw $this->createAccessDeniedException('You cannot modify this product.');
        }

        // Redirect to the AI content generation page
        return $this->redirectToRoute('app_ai_generate_content', ['id' => $product->getId()]);
    }
}