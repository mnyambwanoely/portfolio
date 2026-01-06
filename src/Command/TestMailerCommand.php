<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:test-mailer',
    description: 'Test email sending configuration'
)]
class TestMailerCommand extends Command
{
    private $mailer;
    private $logger;

    public function __construct(MailerInterface $mailer = null, LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Testing mailer configuration...');

        if (!$this->mailer) {
            $output->writeln('<error>Mailer is not configured!</error>');
            return Command::FAILURE;
        }

        try {
            $email = (new Email())
                ->from('mnyambwanoel123@gmail.com')
                ->to('mnyambwanoel123@gmail.com')
                ->subject('🔔 Test Email from Portfolio')
                ->html('<h1>Test Email</h1><p>If you see this, your mailer is working correctly!</p>')
                ->text('Test Email: If you see this, your mailer is working correctly!');

            $this->mailer->send($email);
            $output->writeln('<info>✅ Email sent successfully!</info>');
            
            if ($this->logger) {
                $this->logger->info('Test email sent successfully');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>❌ Email sending failed!</error>');
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            
            if ($this->logger) {
                $this->logger->error('Test email failed: ' . $e->getMessage());
            }

            return Command::FAILURE;
        }
    }
}
