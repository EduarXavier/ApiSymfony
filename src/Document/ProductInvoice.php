<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[EmbeddedDocument]
class ProductInvoice
{
    #[MongoDB\Id(strategy: "UUID")]
    protected string $id;

    #[MongoDB\Field(type: 'string')]
    private string $code;

    #[MongoDB\Field(type: 'string')]
    private string $name;

    #[MongoDB\Field(type:'int')]
    private int $price;

    #[MongoDB\Field(type:'int')]
    private int $amount;

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }
}