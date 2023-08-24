<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;


#[MongoDB\Document]
class Users
{

    #[MongoDB\Id()]
    private string $id;

    #[MongoDB\Field(type: "string")]
    private string $name;

    #[MongoDB\Field(type:"string")]
    #[MongoDB\UniqueIndex(background: true)]
    private string $document;

     #[MongoDB\Field(type:"string")]
    private string $address;

    #[MongoDB\Field(type:"string")]
    private string $phone;

    #[MongoDB\Field(type:"string")]
    #[MongoDB\UniqueIndex(background: true)]
    #[MongoDB\Index(background: true)]
    private string $email;

    #[MongoDB\Field(type:"string")]
    private string $password;

    #[MongoDB\Field(type:"string")]
    private string $recoveryCode;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDocument(): string
    {
        return $this->document;
    }

    public function setDocument(string $document): void
    {
        $this->document = $document;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getRecoveryCode(): string
    {
        return $this->recoveryCode;
    }

    public function setRecoveryCode(string $recoveryCode): void
    {
        $this->recoveryCode = $recoveryCode;
    }

}