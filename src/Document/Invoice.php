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

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function getUserDocument(): string
    {
        return $this->userDocument;
    }

    public function setUserDocument(string $userDocument): void
    {
        $this->userDocument = $userDocument;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
