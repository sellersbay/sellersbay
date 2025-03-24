<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    public const TYPE_CREDIT_PURCHASE = 'credit_purchase';
    public const TYPE_SUBSCRIPTION_PAYMENT = 'subscription_payment';
    public const TYPE_CREDIT_USAGE = 'credit_usage';
    public const TYPE_ADMIN_ADJUSTMENT = 'admin_adjustment';
    public const TYPE_ADDON_CREDIT_PURCHASE = 'addon_credit_purchase';
    public const TYPE_ADDON_CREDIT_USAGE = 'addon_credit_usage';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $packageOrPlan = null;

    #[ORM\Column(nullable: true)]
    private ?int $credits = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPackageOrPlan(): ?string
    {
        return $this->packageOrPlan;
    }

    public function setPackageOrPlan(?string $packageOrPlan): static
    {
        $this->packageOrPlan = $packageOrPlan;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(?int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function getStripePaymentId(): ?string
    {
        return $this->stripePaymentId;
    }

    public function setStripePaymentId(?string $stripePaymentId): static
    {
        $this->stripePaymentId = $stripePaymentId;

        return $this;
    }

    /**
     * Create a credit purchase transaction
     */
    public static function createCreditPurchase(User $user, float $amount, int $credits, string $package, ?string $stripePaymentId = null): self
    {
        $transaction = new self();
        $transaction->setUser($user)
            ->setType(self::TYPE_CREDIT_PURCHASE)
            ->setAmount($amount)
            ->setCredits($credits)
            ->setPackageOrPlan($package)
            ->setStripePaymentId($stripePaymentId)
            ->setDescription("Purchase of $credits credits ($package package)");
        
        return $transaction;
    }
    
    /**
     * Create an add-on credit purchase transaction
     */
    public static function createAddonCreditPurchase(User $user, float $amount, int $credits, string $package, ?string $stripePaymentId = null): self
    {
        $transaction = new self();
        $transaction->setUser($user)
            ->setType(self::TYPE_ADDON_CREDIT_PURCHASE)
            ->setAmount($amount)
            ->setCredits($credits)
            ->setPackageOrPlan($package)
            ->setStripePaymentId($stripePaymentId)
            ->setDescription("Purchase of $credits add-on credits ($package package)");
        
        return $transaction;
    }
    
    /**
     * Create a subscription payment transaction
     */
    public static function createSubscriptionPayment(User $user, float $amount, string $plan, ?string $stripePaymentId = null): self
    {
        $transaction = new self();
        $transaction->setUser($user)
            ->setType(self::TYPE_SUBSCRIPTION_PAYMENT)
            ->setAmount($amount)
            ->setPackageOrPlan($plan)
            ->setStripePaymentId($stripePaymentId)
            ->setDescription("Subscription payment for $plan plan");
        
        return $transaction;
    }
    
    /**
     * Create a credit usage transaction
     */
    public static function createCreditUsage(User $user, int $credits, string $description): self
    {
        $transaction = new self();
        $transaction->setUser($user)
            ->setType(self::TYPE_CREDIT_USAGE)
            ->setAmount(0) // No direct financial amount
            ->setCredits($credits)
            ->setDescription($description);
        
        return $transaction;
    }
    
    /**
     * Create an add-on credit usage transaction
     */
    public static function createAddonCreditUsage(User $user, int $credits, string $description): self
    {
        $transaction = new self();
        $transaction->setUser($user)
            ->setType(self::TYPE_ADDON_CREDIT_USAGE)
            ->setAmount(0) // No direct financial amount
            ->setCredits($credits)
            ->setDescription($description);
        
        return $transaction;
    }
    
    /**
     * Create an admin adjustment transaction
     */
    public static function createAdminAdjustment(User $user, int $credits, string $reason): self
    {
        $transaction = new self();
        $transaction->setUser($user)
            ->setType(self::TYPE_ADMIN_ADJUSTMENT)
            ->setAmount(0) // No direct financial amount
            ->setCredits($credits)
            ->setDescription("Admin adjustment: $reason");
        
        return $transaction;
    }
}