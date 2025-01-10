<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use App\Entity\Produits;

class CartService
{
    private $session;
    private $entityManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->session = $requestStack->getSession();
        $this->entityManager = $entityManager;
    }

    public function addToCart(int $id): void
    {
        $cart = $this->session->get('cart', []);

        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }

        $this->session->set('cart', $cart);
    }

    public function removeFromCart(int $id): void
    {
        $cart = $this->session->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $this->session->set('cart', $cart);
    }

    public function clearCart(): void
    {
        $this->session->remove('cart');
    }

    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }

    public function getFullCart(): array
    {
        $cart = $this->getCart();
        $fullCart = [];

        foreach ($cart as $id => $quantity) {
            $product = $this->entityManager->getRepository(Produits::class)->find($id);

            if ($product && $product->isProduitSupp()) {
                $fullCart[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
            }
        }

        return $fullCart;
    }

    public function getTotal(): float
    {
        $cart = $this->getFullCart();
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['product']->getPrix() * $item['quantity'];
        }

        return $total;
    }



    public function updateQuantity(int $productId, int $quantity): void
    {
        $cart = $this->session->get('cart', []);

        if (isset($cart[$productId]) && $quantity > 0) {
            $cart[$productId] = $quantity; // Mettez à jour la quantité directement
        } elseif ($quantity <= 0) {
            unset($cart[$productId]); // Supprimez l'article si la quantité est 0 ou moins
        }

        $this->session->set('cart', $cart);
    }
}
