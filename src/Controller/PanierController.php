<?php

namespace App\Controller;

use App\Service\CartService;
use App\Repository\LogosRepository;
use App\Repository\CategorieRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PanierController extends AbstractController
{
    private $categorieRepo;
    private $logoRepo;

    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }


    //Gestions du panier
    #[Route('/panier', name: 'cart')]
    public function panier(CartService $cartService): Response
    {
        $cartItems = $cartService->getFullCart();
        $total = $cartService->getTotal();

        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();



        return $this->render('panier/cart.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos

        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add(CartService $cartService, int $id): RedirectResponse
    {
        $cartService->addToCart($id);
        return $this->redirectToRoute('cart');
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove(CartService $cartService, int $id): RedirectResponse
    {
        $cartService->removeFromCart($id);
        return $this->redirectToRoute('cart');
    }

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clear(CartService $cartService): RedirectResponse
    {
        $cartService->clearCart();
        return $this->redirectToRoute('cart');
    }


    // Mettre à jour la quantité du panier via Ajax
    #[Route('/cart/update', name: 'cart_update', methods: ['POST'])]
    public function update(Request $request, CartService $cartService)
    {
        $data = json_decode($request->getContent(), true);
        $productId = $data['productId'];
        $quantity = (int) $data['quantity'];

        $cartService->updateQuantity($productId, $quantity);

        // Récupérer le total du produit et le total global du panier
        $productTotal = $cartService->getFullCart()[$productId]['product']->getPrice() * $quantity;
        $cartTotal = $cartService->getTotal();

        // Retourner les données mises à jour en JSON
        return new JsonResponse([
            'productTotal' => $productTotal,
            'cartTotal' => $cartTotal
        ]);
    }
}
