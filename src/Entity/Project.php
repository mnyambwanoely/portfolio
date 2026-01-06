<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 50)]
    private ?string $category = 'web';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $technologies = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $projectDate = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $liveUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $githubUrl = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'published'; // STATUS FIELD ADDED HERE

    #[ORM\Column]
    private ?bool $isPublished = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $displayOrder = 0;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $screenshotPath = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->projectDate = new \DateTime();
        $this->isPublished = true;
        $this->displayOrder = 0;
        $this->status = 'published'; // Default status
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getTechnologies(): ?string
    {
        return $this->technologies;
    }

    public function setTechnologies(?string $technologies): self
    {
        $this->technologies = $technologies;
        return $this;
    }

    public function getTechnologiesArray(): array
    {
        if (!$this->technologies) {
            return [];
        }
        
        $techs = explode(',', $this->technologies);
        return array_map('trim', $techs);
    }

    public function getProjectDate(): ?\DateTimeInterface
    {
        return $this->projectDate;
    }

    public function setProjectDate(\DateTimeInterface $projectDate): self
    {
        $this->projectDate = $projectDate;
        return $this;
    }

    public function getLiveUrl(): ?string
    {
        return $this->liveUrl;
    }

    public function setLiveUrl(?string $liveUrl): self
    {
        $this->liveUrl = $liveUrl;
        return $this;
    }

    public function getGithubUrl(): ?string
    {
        return $this->githubUrl;
    }

    public function setGithubUrl(?string $githubUrl): self
    {
        $this->githubUrl = $githubUrl;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;
        
        // Sync status with isPublished if needed
        if ($isPublished && $this->status !== 'published') {
            $this->status = 'published';
        } elseif (!$isPublished && $this->status === 'published') {
            $this->status = 'draft';
        }
        
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(?int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getScreenshotPath(): ?string
    {
        return $this->screenshotPath;
    }

    public function setScreenshotPath(?string $screenshotPath): self
    {
        $this->screenshotPath = $screenshotPath;
        return $this;
    }

    public function getCategoryBadgeColor(): string
    {
        $colors = [
            'web' => 'primary',
            'mobile' => 'success',
            'network' => 'info',
            'design' => 'warning',
            'other' => 'secondary'
        ];
        
        return $colors[$this->category] ?? 'secondary';
    }

    public function getStatusBadgeColor(): string
    {
        $colors = [
            'published' => 'success',
            'draft' => 'secondary',
            'archived' => 'warning',
            'pending' => 'info'
        ];
        
        return $colors[$this->status] ?? 'secondary';
    }

    public function getStatusLabel(): string
    {
        $labels = [
            'published' => 'Published',
            'draft' => 'Draft',
            'archived' => 'Archived',
            'pending' => 'Pending Review'
        ];
        
        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function __toString(): string
    {
        return $this->title ?? 'Project #' . $this->id;
    }
}