<?php

namespace App\Entity;

use App\Repository\SubscriptionPlanRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubscriptionPlanRepository::class)]
#[ORM\Table(name: 'subscription_plan')]
#[ORM\HasLifecycleCallbacks]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $identifier = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private float $price = 0.0;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private int $credits = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var array<string, bool> Features offered by this plan
     */
    #[ORM\Column(type: 'json')]
    private array $features = [];

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $isFeatured = false;

    #[ORM\Column]
    private int $displayOrder = 1;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeProductId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePriceId = null;

    #[ORM\Column(nullable: true)]
    private ?int $discount = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['monthly', 'yearly'])]
    private string $term = 'monthly';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var array<string> Array of feature descriptions
     */
    #[ORM\Column(type: 'json')]
    private array $featureDescriptions = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->features = [
            'product_descriptions' => true,
            'meta_descriptions' => true,
            'image_alt_text' => true,
            'seo_keywords' => false,
            'premium_ai' => false
        ];
        $this->featureDescriptions = [];
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDiscountedPrice(): float
    {
        if ($this->discount === null || $this->discount <= 0) {
            return $this->price;
        }

        return $this->price * (1 - ($this->discount / 100));
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): static
    {
        $this->features = $features;

        return $this;
    }

    public function hasFeature(string $feature): bool
    {
        return $this->features[$feature] ?? false;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getStripeProductId(): ?string
    {
        return $this->stripeProductId;
    }

    public function setStripeProductId(?string $stripeProductId): static
    {
        $this->stripeProductId = $stripeProductId;

        return $this;
    }

    public function getStripePriceId(): ?string
    {
        return $this->stripePriceId;
    }

    public function setStripePriceId(?string $stripePriceId): static
    {
        $this->stripePriceId = $stripePriceId;

        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(?int $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function setTerm(string $term): static
    {
        $this->term = $term;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFeatureDescriptions(): array
    {
        return $this->featureDescriptions;
    }

    public function setFeatureDescriptions(array $featureDescriptions): static
    {
        $this->featureDescriptions = $featureDescriptions;

        return $this;
    }

    public function addFeatureDescription(string $description): static
    {
        $this->featureDescriptions[] = $description;

        return $this;
    }

    /**
     * Get plan data as an array for templates
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->getDiscountedPrice(),
            'original_price' => $this->price,
            'credits' => $this->credits,
            'description' => $this->description,
            'features' => $this->features,
            'feature_descriptions' => $this->featureDescriptions,
            'active' => $this->isActive,
            'featured' => $this->isFeatured,
            'discount' => $this->discount,
            'term' => $this->term
        ];
    }
}