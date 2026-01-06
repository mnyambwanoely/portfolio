<?php

namespace App\Command;

use App\Entity\Education;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-education',
    description: 'Add education records to the database'
)]
class AddEducationCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Add Diploma
        $diploma = new Education();
        $diploma->setSchool('Institute of Accountancy Arusha');
        $diploma->setDegree('Diploma in Computer Science');
        $diploma->setFieldOfStudy('Computer Science');
        $diploma->setGpa('3.2/5.0');
        $diploma->setDescription('Comprehensive training in computer science fundamentals, software development, and IT infrastructure.');
        $diploma->setIsActive(true);
        $diploma->setStartDate(new \DateTime('2020-01-01'));
        $diploma->setEndDate(new \DateTime('2023-12-31'));

        $this->entityManager->persist($diploma);
        $io->success('✅ Added: Diploma in Computer Science');

        // Add Bachelor's Degree (Pending)
        $bachelor = new Education();
        $bachelor->setSchool('University (To be specified)');
        $bachelor->setDegree("Bachelor's Degree (Pending)");
        $bachelor->setFieldOfStudy('Computer Science / Related Field');
        $bachelor->setDescription('Completion expected in 2025. Currently awaiting the issuance of Bachelor\'s degree completion.');
        $bachelor->setIsActive(true);
        $bachelor->setStartDate(new \DateTime('2023-01-01'));
        $bachelor->setEndDate(null); // No end date since it's pending

        $this->entityManager->persist($bachelor);
        $io->success("✅ Added: Bachelor's Degree (Pending)");

        $this->entityManager->flush();
        $io->success('✅ All education records saved to database successfully!');

        return Command::SUCCESS;
    }
}
