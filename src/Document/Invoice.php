<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\Common\Collections\ArrayCollection;
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

    #[EmbedMany(targetDocument: ProductInvoice::class)]
    private ArrayCollection $products;

    #[Field(type:'string')]
    private string $date;

    #[EmbedOne(targetDocument : UserInvoice::class)]
    private UserInvoice $user;

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

    public function getProducts(): ArrayCollection
    {
        return $this->products;
    }

    public function setProducts(ArrayCollection $products): static
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

    public function removeProduct(ProductInvoice $product): bool
    {
        return $this->products->removeElement($product);
    }

    public function getUser(): UserInvoice
    {
        return $this->user;
    }

    public function setUser(UserInvoice $user): static
    {
        $this->user = $user;

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
