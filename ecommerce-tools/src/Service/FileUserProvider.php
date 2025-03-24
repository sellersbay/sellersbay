<?php

namespace App\Service;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use App\Entity\User;

/**
 * File-based user provider that doesn't require database connectivity
 */
class FileUserProvider implements UserProviderInterface
{
    private $usersFile;
    private $users = [];

    public function __construct(string $usersFile = null)
    {
        $this->usersFile = $usersFile ?? __DIR__ . '/../../var/users.json';
        $this->loadUsers();
    }

    /**
     * Load users from JSON file or create default users if file doesn't exist
     */
    private function loadUsers(): void
    {
        if (file_exists($this->usersFile)) {
            $userData = json_decode(file_get_contents($this->usersFile), true);
            foreach ($userData as $email => $data) {
                $user = new User();
                $user->setEmail($email);
                $user->setPassword($data['password']);
                $user->setRoles($data['roles']);
                $this->users[$email] = $user;
            }
        } else {
            // Create default user and save to file
            $this->createDefaultUsers();
        }
    }

    /**
     * Create default users when no user file exists
     */
    private function createDefaultUsers(): void
    {
        $user = new User();
        $user->setEmail('sellersbay@gmail.com');
        $user->setPassword('$2y$13$pNp9mezIMLI9UPcmMzYiVu.gd0UPiGx9vmvPXpvR3QUmRxU3Ktv4S'); // "powder04" hashed
        $user->setRoles(['ROLE_USER']);
        
        $this->users[$user->getEmail()] = $user;
        
        // Save to file
        $this->saveUsers();
    }

    /**
     * Save users to JSON file
     */
    private function saveUsers(): void
    {
        $data = [];
        foreach ($this->users as $email => $user) {
            $data[$email] = [
                'password' => $user->getPassword(),
                'roles' => $user->getRoles(),
            ];
        }
        
        $dir = dirname($this->usersFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        file_put_contents($this->usersFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (isset($this->users[$identifier])) {
            return $this->users[$identifier];
        }

        throw new UserNotFoundException(sprintf('Username "%s" does not exist.', $identifier));
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $email = $user->getEmail();
        if (!isset($this->users[$email])) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $email));
        }

        return $this->users[$email];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Add a new user to the file
     */
    public function addUser(User $user): void
    {
        $this->users[$user->getEmail()] = $user;
        $this->saveUsers();
    }
}