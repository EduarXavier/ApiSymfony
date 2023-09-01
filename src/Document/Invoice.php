<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class Invoice
{
    #[MongoDB\Id()]
    private string $id;

    #[MongoDB\Field(type:'collection')]
    private array $products;

    #[MongoDB\Field(type:'string')]
    private string $date;

    #[MongoDB\Field(type:'string')]
    private string $userDocument;

    #[MongoDB\Field(type:'string')]
    private string $status;

    public function getId(): string
    {
        return $this->id;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function setProducts(array $products): static
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

    public function getUserDocument(): string
    {
        return $this->userDocument;
    }

    public function setUserDocument(string $userDocument): static
    {
        $this->userDocument = $userDocument;

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
