<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Question;
use App\Entity\Reponse;

#[ORM\Entity(repositoryClass: "App\Repository\MessageRepository")]
#[ORM\Table(name: "message")]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(length: 50)]
    private string $sender;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id', nullable: false)]
    private ?Question $question = null;

   
   
    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'reponse_id', referencedColumnName: 'id', nullable: false)]
    private ?Reponse $reponse = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setSender(string $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getReponse(): ?Reponse
    {
        return $this->reponse;
    }

    public function setReponse(?Reponse $reponse): self
    {
        $this->reponse = $reponse;
        return $this;
    }
}