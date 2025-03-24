<?php

namespace App\Entity;

use App\Repository\AddonCreditRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddonCreditRepository::class)]
#[ORM\Table(name: 'addon_credit')]
#[ORM\HasLifecycleCallbacks]
class AddonCredit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'addonCredits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private int $amount = 0;

    #[ORM\Column]
    private int $remainingAmount = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $packageName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transactionId = null;

    #[ORM\Column]
    private ?float $purchasePrice = 0.0;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;
        
        // If this is a new record, also set the remaining amount
        if ($this->remainingAmount === 0) {
            $this->remainingAmount = $amount;
        }

        return $this;
    }

    public function getRemainingAmount(): int
    {
        return $this->remainingAmount;
    }

    public function setRemainingAmount(int $remainingAmount): static
    {
        $this->remainingAmount = $remainingAmount;

        return $this;
    }

    public function useCredits(int $amount): bool
    {
        if ($this->remainingAmount >= $amount) {
            $this->remainingAmount -= $amount;
            $this->lastUsedAt = new \DateTimeImmutable();
            
            // If all credits are used, mark as inactive
            if ($this->remainingAmount <= 0) {
                $this->isActive = false;
            }
            
            return true;
        }
        
        return false;
    }

    public function getPackageName(): ?string
    {
        return $this->packageName;
    }

    public function setPackageName(?string $packageName): static
    {
        $this->packageName = $packageName;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): static
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getPurchasePrice(): ?float
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(float $purchasePrice): static
    {
        $this->purchasePrice = $purchasePrice;

        return $this;
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

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Create a new add-on credit entry from a package purchase
     */
    public static function createFromPackage(User $user, PackageAddOn $package, float $purchasePrice, ?string $transactionId = null): self
    {
        $addonCredit = new self();
        $addonCredit->setUser($user)
            ->setAmount($package->getCredits())
            ->setRemainingAmount($package->getCredits())
            ->setPackageName($package->getName())
            ->setPurchasePrice($purchasePrice)
            ->setTransactionId($transactionId)
            ->setIsActive(true);
        
        return $addonCredit;
    }

    /**
     * Create a new add-on credit entry from a manual adjustment
     */
    public static function createFromAdjustment(User $user, int $amount, string $notes): self
    {
        $addonCredit = new self();
        $addonCredit->setUser($user)
            ->setAmount($amount)
            ->setRemainingAmount($amount)
            ->setPackageName('Admin Adjustment')
            ->setPurchasePrice(0)
            ->setIsActive(true)
            ->setNotes($notes);
        
        return $addonCredit;
    }
}