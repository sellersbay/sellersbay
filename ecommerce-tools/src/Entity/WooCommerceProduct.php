<?php

namespace App\Entity;

use App\Repository\WooCommerceProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WooCommerceProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class WooCommerceProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $woocommerceId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $imageAltText = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $targetKeyphrase = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(length: 50)]
    private string $status = 'imported';
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $originalData = null;
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiResponse = null;
    #[ORM\Column(length: 255)]
    private ?string $storeUrl = null;
    #[ORM\Column(length: 255)]
    private ?string $consumerKey = null;
    #[ORM\Column(length: 255)]
    private ?string $consumerSecret = null;
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getWoocommerceId(): ?int
    {
        return $this->woocommerceId;
    }
    public function setWoocommerceId(int $woocommerceId): static
    {
        $this->woocommerceId = $woocommerceId;
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
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }
    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }
    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }
    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }
    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }
    public function getImageAltText(): ?string
    {
        return $this->imageAltText;
    }

    public function getTargetKeyphrase(): ?string
    {
        return $this->targetKeyphrase;
    }

    public function setTargetKeyphrase(?string $targetKeyphrase): static
    {
        $this->targetKeyphrase = $targetKeyphrase;
        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }
    public function setImageAltText(?string $imageAltText): static
    {
        $this->imageAltText = $imageAltText;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    public function getOriginalData(): ?array
    {
        return $this->originalData;
    }

    public function setOriginalData(?array $originalData): static
    {
        $this->originalData = $originalData;
        return $this;
    }

    public function getApiResponse(): ?array
    {
        return $this->apiResponse;
    }

    public function setApiResponse(?array $apiResponse): static
    {
        $this->apiResponse = $apiResponse;
        return $this;
    }

    public function getStoreUrl(): ?string
    {
        return $this->storeUrl;
    }

    public function setStoreUrl(string $storeUrl): static
    {
        $this->storeUrl = $storeUrl;
        return $this;
    }

    public function getConsumerKey(): ?string
    {
        return $this->consumerKey;
    }

    public function setConsumerKey(string $consumerKey): static
    {
        $this->consumerKey = $consumerKey;
        return $this;
    }

    public function getConsumerSecret(): ?string
    {
        return $this->consumerSecret;
    }

    public function setConsumerSecret(string $consumerSecret): static
    {
        $this->consumerSecret = $consumerSecret;
        return $this;
    }
}