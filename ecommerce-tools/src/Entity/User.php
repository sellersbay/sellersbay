<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private int $credits = 0;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $subscriptionTier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;
    
    /**
     * Subscription ID from Stripe - not persisted to database
     * 
     * @var string|null
     */
    private ?string $stripeSubscriptionId = null;
    
    /**
     * Next billing date - not persisted to database
     * 
     * @var \DateTime|null
     */
    private ?\DateTime $nextBillingDate = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;
    
    // Making these fields nullable with proper annotations
    // No migration needed, but they'll be available for the controller
    #[ORM\Column(length: 255, nullable: true, options: ["default" => null])]
    private ?string $woocommerceStoreUrl = null;
    
    #[ORM\Column(length: 255, nullable: true, options: ["default" => null])]
    private ?string $woocommerceConsumerKey = null;
    
    #[ORM\Column(length: 255, nullable: true, options: ["default" => null])]
    private ?string $woocommerceConsumerSecret = null;

    /**
     * @var Collection<int, AddonCredit>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AddonCredit::class, orphanRemoval: true)]
    private Collection $addonCredits;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->credits = 0;
        $this->roles = ['ROLE_USER'];
        $this->addonCredits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
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

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function addCredits(int $amount): static
    {
        $this->credits += $amount;

        return $this;
    }

    /**
     * Use subscription credits
     * This method will only use credits from the user's subscription balance
     */
    public function useCredits(int $amount): static
    {
        if ($this->credits >= $amount) {
            $this->credits -= $amount;
        }

        return $this;
    }
    
    /**
     * @return Collection<int, AddonCredit>
     */
    public function getAddonCredits(): Collection
    {
        return $this->addonCredits;
    }
    
    public function addAddonCredit(AddonCredit $addonCredit): static
    {
        if (!$this->addonCredits->contains($addonCredit)) {
            $this->addonCredits->add($addonCredit);
            $addonCredit->setUser($this);
        }
        
        return $this;
    }
    
    public function removeAddonCredit(AddonCredit $addonCredit): static
    {
        if ($this->addonCredits->removeElement($addonCredit)) {
            // set the owning side to null (unless already changed)
            if ($addonCredit->getUser() === $this) {
                $addonCredit->setUser(null);
            }
        }
        
        return $this;
    }
    
    /**
     * Get total available add-on credits
     * This counts only active add-on credits that have remaining balance
     */
    public function getTotalAddonCredits(): int
    {
        $total = 0;
        foreach ($this->addonCredits as $addonCredit) {
            if ($addonCredit->isActive()) {
                $total += $addonCredit->getRemainingAmount();
            }
        }
        
        return $total;
    }
    
    /**
     * Get total credits (subscription + add-on)
     */
    public function getTotalCredits(): int
    {
        return $this->credits + $this->getTotalAddonCredits();
    }
    
    /**
     * Use add-on credits first, then use subscription credits if needed
     * Returns true if successful, false if not enough credits
     */
    public function useAllCredits(int $amount): bool
    {
        $totalAvailable = $this->getTotalCredits();
        
        if ($totalAvailable < $amount) {
            return false;
        }
        
        // First use add-on credits
        $remainingToUse = $amount;
        
        // Sort add-on credits by creation date (oldest first)
        $activeAddons = $this->addonCredits->filter(
            fn(AddonCredit $credit) => $credit->isActive() && $credit->getRemainingAmount() > 0
        )->toArray();
        
        usort($activeAddons, function(AddonCredit $a, AddonCredit $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        
        // Use add-on credits first (oldest first)
        foreach ($activeAddons as $addonCredit) {
            $available = $addonCredit->getRemainingAmount();
            if ($available >= $remainingToUse) {
                $addonCredit->useCredits($remainingToUse);
                $remainingToUse = 0;
                break;
            } else {
                $addonCredit->useCredits($available);
                $remainingToUse -= $available;
            }
        }
        
        // If there are still credits to use, use subscription credits
        if ($remainingToUse > 0) {
            $this->useCredits($remainingToUse);
        }
        
        return true;
    }

    public function getSubscriptionTier(): ?string
    {
        return $this->subscriptionTier;
    }

    public function setSubscriptionTier(?string $subscriptionTier): static
    {
        $this->subscriptionTier = $subscriptionTier;

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }
    
    /**
     * Get the Stripe subscription ID
     */
    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }
    
    /**
     * Set the Stripe subscription ID
     */
    public function setStripeSubscriptionId(?string $stripeSubscriptionId): static
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;
        
        return $this;
    }
    
    /**
     * Get the next billing date
     */
    public function getNextBillingDate(): ?\DateTime
    {
        return $this->nextBillingDate;
    }
    
    /**
     * Set the next billing date
     */
    public function setNextBillingDate(?\DateTime $nextBillingDate): static
    {
        $this->nextBillingDate = $nextBillingDate;
        
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }
    
    public function getWoocommerceStoreUrl(): ?string
    {
        return $this->woocommerceStoreUrl;
    }
    
    public function setWoocommerceStoreUrl(?string $woocommerceStoreUrl): static
    {
        $this->woocommerceStoreUrl = $woocommerceStoreUrl;
        
        return $this;
    }
    
    public function getWoocommerceConsumerKey(): ?string
    {
        return $this->woocommerceConsumerKey;
    }
    
    public function setWoocommerceConsumerKey(?string $woocommerceConsumerKey): static
    {
        $this->woocommerceConsumerKey = $woocommerceConsumerKey;
        
        return $this;
    }
    
    public function getWoocommerceConsumerSecret(): ?string
    {
        return $this->woocommerceConsumerSecret;
    }
    
    public function setWoocommerceConsumerSecret(?string $woocommerceConsumerSecret): static
    {
        $this->woocommerceConsumerSecret = $woocommerceConsumerSecret;
        
        return $this;
    }
}