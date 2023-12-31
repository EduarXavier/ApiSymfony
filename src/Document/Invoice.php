<?php

declare(strict_types=1);

namespace App\Document;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class Invoice
{
    public const SHOPPING_CART = 'shopping-cart';

    public const INVOICE = 'invoice';

    public const CANCEL = 'cancel';

    public const PAY = 'pay';

    #[MongoDb\Id(strategy: 'auto')]
    protected ?string $id;

    #[MongoDb\Field(type:'string')]
    private string $code;

    #[MongoDb\EmbedMany(targetDocument: ProductInvoice::class)]
    private Collection $products;

    #[MongoDb\Field(type:'date')]
    private DateTime $date;

    #[MongoDb\ReferenceOne(targetDocument : User::class, cascade: 'persist')]
    private User $user;

    #[MongoDb\Field(type:'string')]
    private string $status;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?string
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

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function addProduct(ProductInvoice $product): static
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
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

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->products as $product) {
            $total += $product->getAmount() * $product->getPrice();
        }

        return $total;
    }
}
