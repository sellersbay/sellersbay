<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-regular-user',
    description: 'Creates a regular user with proper initialization',
)]
class CreateRegularUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email address', 'sellersbay@gmail.com')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password', 'password123')
            ->addArgument('firstName', InputArgument::OPTIONAL, 'First name', 'Test')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'Last name', 'User')
            ->addArgument('credits', InputArgument::OPTIONAL, 'Initial credits', 50)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $credits = (int)$input->getArgument('credits');

        $userRepository = $this->entityManager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->note(sprintf('Updating existing user: %s', $email));
            $user = $existingUser;
        } else {
            $io->note(sprintf('Creating new user: %s', $email));
            $user = new User();
            $user->setEmail($email);
            // These are initialized in constructor, but let's be explicit
            $user->setCreatedAt(new \DateTimeImmutable());
        }

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Set ONLY ROLE_USER (to avoid the admin role)
        $user->setRoles(['ROLE_USER']);
        
        // Set required fields
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $user->setCredits($credits);
        $user->setIsVerified(true); // Set as verified for testing
        
        // Ensure WooCommerce fields are initialized (can be null)
        $user->setWoocommerceStoreUrl(null);
        $user->setWoocommerceConsumerKey(null);
        $user->setWoocommerceConsumerSecret(null);
        
        // Set other fields
        $user->setSubscriptionTier('free');
        
        // Save to database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Regular user created/updated successfully!');
        $io->table(['Property', 'Value'], [
            ['Email', $email],
            ['Password', $password],
            ['Roles', implode(', ', $user->getRoles())],
            ['First Name', $firstName],
            ['Last Name', $lastName],
            ['Credits', $credits],
        ]);
        
        $io->text('You can now log in at /login');
        
        return Command::SUCCESS;
    }
}