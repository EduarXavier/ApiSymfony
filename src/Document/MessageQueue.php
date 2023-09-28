<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class MessageQueue
{
    #[MongoDB\Id]
    protected ?string $id;

    #[MongoDB\Field(type: 'bool')]
    private ?bool $processed;

    #[MongoDB\Field(type: 'bool')]
    private ?bool $rejected;

    #[MongoDb\ReferenceOne(targetDocument : User::class, cascade: 'persist')]
    private ?User $user;

    #[MongoDB\Field(type: 'string')]
    private ?string $type;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getProcessed(): ?bool
    {
        return $this->processed;
    }

    public function setProcessed(?bool $processed): static
    {
        $this->processed = $processed;
        
        return $this;
    }

    public function getRejected(): ?bool
    {
        return $this->rejected;
    }

    public function setRejected(?bool $rejected): static
    {
        $this->rejected = $rejected;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
