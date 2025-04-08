<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Message;

#[ORM\Entity(repositoryClass: "App\Repository\ReponseRepository")]
#[ORM\Table(name: "reponse")]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $username;

    #[ORM\Column(length: 100)]
    private string $role;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'reponses')]
    private ?Message $message = null;

    #[ORM\OneToMany(mappedBy: 'reponse', targetEntity: Message::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username):self
    {
        $this->username = $username;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setReponse($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getReponse() === $this) {
                $message->setReponse(null);
            }
        }

        return $this;
    }
}