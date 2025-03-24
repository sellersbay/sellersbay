<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user or promotes an existing user to admin',
)]
class CreateAdminUserCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email address for the admin user', 'sellersbay@example.com')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password for the admin user', '123456')
            ->addArgument('firstName', InputArgument::OPTIONAL, 'First name', 'Admin')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'Last name', 'User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');

        // Check if the user already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);

        if ($existingUser) {
            $user = $existingUser;
            $io->note(sprintf('Updating existing user: %s', $email));
        } else {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $io->note(sprintf('Creating new admin user: %s', $email));
        }

        // Set the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Set roles and ensure ROLE_ADMIN is included
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles(array_unique($roles));
        }

        // Set is verified
        $user->setIsVerified(true);

        // Add some initial credits
        $user->setCredits(100);

        // Persist to database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf(
            'Admin user created/updated successfully! 
            Email: %s
            Password: %s
            Roles: %s
            You can now log in at /login and access the admin area.',
            $email,
            $password,
            implode(', ', $user->getRoles())
        ));

        return Command::SUCCESS;
    }
}