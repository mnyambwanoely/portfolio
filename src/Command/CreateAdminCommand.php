<?php

namespace App\Command;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user',
)]
class CreateAdminCommand extends Command
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
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email')
            ->addArgument('username', InputArgument::OPTIONAL, 'Admin username')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force update if user exists')
            ->setHelp('This command allows you to create an admin user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $force = $input->getOption('force');
        
        // If no arguments provided, use interactive mode
        if (!$email || !$username || !$password) {
            $io->title('Admin User Creator');
            $io->section('Please provide admin user details');
            
            // Ask for email if not provided
            if (!$email) {
                $email = $io->ask('Email address', null, function($value) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        throw new \RuntimeException('Please enter a valid email address.');
                    }
                    return $value;
                });
            }
            
            // Ask for username if not provided
            if (!$username) {
                $username = $io->ask('Username', null, function($value) {
                    if (empty($value)) {
                        throw new \RuntimeException('Username cannot be empty.');
                    }
                    if (strlen($value) < 3) {
                        throw new \RuntimeException('Username must be at least 3 characters long.');
                    }
                    return $value;
                });
            }
            
            // Ask for password if not provided
            if (!$password) {
                $password = $io->askHidden('Password', function($value) {
                    if (strlen($value) < 6) {
                        throw new \RuntimeException('Password must be at least 6 characters long.');
                    }
                    return $value;
                });
                
                // Confirm password
                $confirmPassword = $io->askHidden('Confirm password', function($value) use ($password) {
                    if ($value !== $password) {
                        throw new \RuntimeException('Passwords do not match.');
                    }
                    return $value;
                });
            }
        }
        
        // Check if user exists
        $existingUser = $this->entityManager->getRepository(Admin::class)->findOneBy(['email' => $email]);
        
        if ($existingUser && !$force) {
            $io->error('A user with this email already exists!');
            $io->note('Use --force option to update existing user');
            return Command::FAILURE;
        }
        
        if ($existingUser && $force) {
            // Update existing admin
            $admin = $existingUser;
            $io->note('Updating existing admin...');
        } else {
            // Create new admin
            $admin = new Admin();
            $admin->setEmail($email);
            $admin->setFullName($username);
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setIsActive(true);
        }
        
        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($admin, $password);
        $admin->setPassword($hashedPassword);
        
        // Save to database
        $this->entityManager->persist($admin);
        $this->entityManager->flush();
        
        $io->success('Admin user ' . ($existingUser ? 'updated' : 'created') . ' successfully!');
        $io->text([
            'Email: ' . $email,
            'Full name: ' . $username,
            'Password: ********',
            'Roles: ROLE_ADMIN',
            '',
            'You can now login at: /admin/login'
        ]);
        
        return Command::SUCCESS;
    }
}