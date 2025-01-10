<?php

// src/Entity/Commande.php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    private ?User $user = null;

    // Cette relation passe par la table intermédiaire CommandeProduits
    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeProduits::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $commandeProduits;

    #[ORM\Column]
    private ?int $prixtotal = null;

    #[ORM\Column(length: 255)]
    private ?string $Matricule = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    private ?MethodePaiement $methodePaiement = null;

    public function __construct()
    {
        $this->commandeProduits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }



    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    // Méthode pour obtenir les produits via la relation intermédiaire CommandeProduits
    public function getProduits(): Collection
    {
        $produits = new ArrayCollection();
        foreach ($this->commandeProduits as $commandeProduit) {
            $produits->add($commandeProduit->getProduit());
        }
        return $produits;
    }

    public function getPrixtotal(): ?int
    {
        return $this->prixtotal;
    }

    public function setPrixtotal(int $prixtotal): static
    {
        $this->prixtotal = $prixtotal;
        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->Matricule;
    }

    public function setMatricule(string $Matricule): static
    {
        $this->Matricule = $Matricule;
        return $this;
    }

    public function getCommandeProduits(): Collection
    {
        return $this->commandeProduits;
    }

    public function getMethodePaiement(): ?MethodePaiement
    {
        return $this->methodePaiement;
    }

    public function setMethodePaiement(?MethodePaiement $methodePaiement): static
    {
        $this->methodePaiement = $methodePaiement;

        return $this;
    }
}
