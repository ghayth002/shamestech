<?php

namespace App\Entity;

use App\Repository\EventMilestoneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventMilestoneRepository::class)]
#[ORM\Table(name: 'eventmilestones')]
class EventMilestone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'milestones')]
    #[ORM\JoinColumn(name: 'id_event', referencedColumnName: 'id_event', nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(name: 'milestones_name', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $name = null;

    #[ORM\Column(name: 'expected_date', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $expectedDate = null;

    #[ORM\Column(name: 'completion_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completionDate = null;

    #[ORM\Column(name: 'statuts', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['Not_Started', 'Started', 'Completed', 'Delay'])]
    private ?string $status = 'Not_Started';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getExpectedDate(): ?\DateTimeInterface
    {
        return $this->expectedDate;
    }

    public function setExpectedDate(\DateTimeInterface $expectedDate): static
    {
        $this->expectedDate = $expectedDate;

        return $this;
    }

    public function getCompletionDate(): ?\DateTimeInterface
    {
        return $this->completionDate;
    }

    public function setCompletionDate(?\DateTimeInterface $completionDate): static
    {
        $this->completionDate = $completionDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
} 