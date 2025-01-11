<?php

namespace App\Entity;

use App\Repository\HeaderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeaderRepository::class)]
class Header
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $petite_titre = null;

    #[ORM\Column(length: 255)]
    private ?string $grand_titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPetiteTitre(): ?string
    {
        return $this->petite_titre;
    }

    public function setPetiteTitre(string $petite_titre): static
    {
        $this->petite_titre = $petite_titre;

        return $this;
    }

    public function getGrandTitre(): ?string
    {
        return $this->grand_titre;
    }

    public function setGrandTitre(string $grand_titre): static
    {
        $this->grand_titre = $grand_titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }
}
