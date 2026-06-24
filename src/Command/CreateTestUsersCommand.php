<?php

namespace App\Command;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Create test users for the application'
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if admin user already exists
        $adminUser = $this->entityManager->getRepository(Users::class)->findOneBy(['email' => 'admin@example.com']);
        if ($adminUser) {
            $io->info('Test users already exist. Skipping creation.');
            return Command::SUCCESS;
        }

        // Create Admin User
        $admin = new Users();
        $admin->setNom('Admin');
        $admin->setPrenom('System');
        $admin->setEmail('admin@example.com');
        $admin->setTelephone('+216 20 000 000');
        $admin->setRole('ROLE_ADMIN');
        
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);

        // Create Regular User
        $user = new Users();
        $user->setNom('User');
        $user->setPrenom('Demo');
        $user->setEmail('user@example.com');
        $user->setTelephone('+216 20 111 111');
        $user->setRole('ROLE_USER');
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'user123');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);

        // Flush to database
        $this->entityManager->flush();

        $io->success('Test users created successfully!');
        $io->table(
            ['Email', 'Role', 'Password'],
            [
                ['admin@example.com', 'ROLE_ADMIN', 'admin123'],
                ['user@example.com', 'ROLE_USER', 'user123'],
            ]
        );

        return Command::SUCCESS;
    }
}
