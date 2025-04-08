<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_event")]
    private ?int $id = null;

    #[ORM\Column(name: "name_event", length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $name = null;

    #[ORM\Column(name: "id_user")]
    private ?int $userId = null;

    #[ORM\Column(name: "date_debut", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: "type_categorie", length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventMilestone::class, orphanRemoval: true)]
    private Collection $milestones;

    public function __construct()
    {
        $this->milestones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, EventMilestone>
     */
    public function getMilestones(): Collection
    {
        return $this->milestones;
    }

    public function addMilestone(EventMilestone $milestone): static
    {
        if (!$this->milestones->contains($milestone)) {
            $this->milestones->add($milestone);
            $milestone->setEvent($this);
        }

        return $this;
    }

    public function removeMilestone(EventMilestone $milestone): static
    {
        if ($this->milestones->removeElement($milestone)) {
            // set the owning side to null (unless already changed)
            if ($milestone->getEvent() === $this) {
                $milestone->setEvent(null);
            }
        }

        return $this;
    }
} 