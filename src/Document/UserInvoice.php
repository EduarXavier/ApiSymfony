<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument;

#[EmbeddedDocument]
class UserInvoice
{
    #[MongoDB\Id()]
    private string $id;

    #[MongoDB\Field(type: 'string')]
    private string $name;

    #[MongoDB\Field(type:'string')]
    #[MongoDB\UniqueIndex(background: true)]
    private string $document;

    #[MongoDB\Field(type:'string')]
    private string $address;

    #[MongoDB\Field(type: 'string')]
    private string $rol;

    #[MongoDB\Field(type:'string')]
    private string $phone;

    #[MongoDB\Field(type:'string')]
    #[MongoDB\UniqueIndex(background: true)]
    #[MongoDB\Index(background: true)]
    private string $email;

    public function setUser(User $user): static
    {
        $this->id = $user->getId();
        $this->name = $user->getName();
        $this->document = $user->getDocument();
        $this->address = $user->getAddress();
        $this->rol = $user->getRol();
        $this->phone = $user->getPhone();
        $this->email = $user->getEmail();

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getDocument(): string
    {
        return $this->document;
    }

    public function setDocument(string $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getRol(): string
    {
        return $this->rol;
    }

    public function setRol(string $rol): static
    {
        $this->rol = $rol;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRecoveryCode(): string
    {
        return $this->recoveryCode;
    }

    public function setRecoveryCode(string $recoveryCode): static
    {
        $this->recoveryCode = $recoveryCode;

        return $this;
    }
}