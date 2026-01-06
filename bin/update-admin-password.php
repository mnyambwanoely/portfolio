<?php
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
}

use App\Kernel;
use App\Entity\Admin;

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$admin = $em->getRepository(Admin::class)->findOneBy(['email' => 'admin@example.test']);
if (!$admin) {
    echo "Admin not found\n";
    exit(1);
}

// New hashed password (generated with `php bin/console security:hash-password "admin123"`)
$hash = '$2y$13$3k0oiuZIdpN4W6C3Dx7wNOWjwhgvUQpCsA267Qv9XAGkKP2a.8WYq';

$admin->setPassword($hash);
$em->flush();

echo "Password updated for {$admin->getEmail()}\n";
