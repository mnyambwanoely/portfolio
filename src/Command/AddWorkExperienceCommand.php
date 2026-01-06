<?php

namespace App\Command;

use App\Entity\WorkExperience;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-work-experience',
    description: 'Add work experience records to the database'
)]
class AddWorkExperienceCommand extends Command
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

        // Add IT Support Specialist at SimbaNet
        $experience = new WorkExperience();
        $experience->setPosition('IT Support Specialist');
        $experience->setCompany('SimbaNet, Dar es Salaam, Tanzania');
        $experience->setStartDate(new \DateTime('2023-07-01'));
        $experience->setEndDate(new \DateTime('2023-09-30'));
        $experience->setIsCurrent(false);
        $experience->setDescription("Fiber installation and maintenance\nNetwork configuration and troubleshooting\nCustomer technical support");
        $experience->setIsActive(true);

        $this->entityManager->persist($experience);
        $io->success('✅ Added: IT Support Specialist at SimbaNet');

        $this->entityManager->flush();
        $io->success('✅ Work experience record saved to database successfully!');

        return Command::SUCCESS;
    }
}
