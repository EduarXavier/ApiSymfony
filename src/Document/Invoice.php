<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbedMany;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbedOne;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

#[MongoDB\Document]
class Invoice
{
    #[Id()]
    private string $id;

    #[Field(type:'string')]
    private string $code;

    #[EmbedMany(targetDocument: Product::class)]
    private Collection $products;

    #[Field(type:'string')]
    private string $date;

    #[EmbedOne(targetDocument : User::class)]
    private User $user;

    #[Field(type:'string')]
    private string $status;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function setProducts(Collection $products): static
    {
        $this->products = $products;

        return $this;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function addProducts(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    public function removeProduct(Product $product): bool
    {
        $products = new ArrayCollection();
        foreach ($this->products as $productArray){
            if ($productArray->getId() != $product->getId()){
                $products->add($productArray);
            }
        }

        $this->products = $products;
        return true;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $userDocument): static
    {
        $this->user = $userDocument;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
