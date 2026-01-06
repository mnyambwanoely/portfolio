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
    name: 'app:fix-work-experience',
    description: 'Fix and display all work experiences'
)]
class FixWorkExperienceCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $workExperiences = $this->em->getRepository(WorkExperience::class)->findAll();
            
            if (empty($workExperiences)) {
                $io->warning('No work experiences found in the database.');
                return Command::SUCCESS;
            }

            $io->title('Work Experiences Status');
            $io->section('Current Work Experiences: ' . count($workExperiences));

            $table = [];
            foreach ($workExperiences as $work) {
                $table[] = [
                    'ID' => $work->getId(),
                    'Company' => $work->getCompany(),
                    'Position' => $work->getPosition(),
                    'Active' => $work->isActive() ? '✓ Yes' : '✗ No',
                    'Current' => $work->isCurrent() ? '✓ Yes' : '✗ No',
                ];
            }

            $io->table(['ID', 'Company', 'Position', 'Active', 'Current'], $table);

            // Set all to active
            $count = 0;
            foreach ($workExperiences as $work) {
                if (!$work->isActive()) {
                    $work->setIsActive(true);
                    $count++;
                }
            }

            if ($count > 0) {
                $this->em->flush();
                $io->success("Activated $count inactive work experience(s)!");
            } else {
                $io->info('All work experiences are already active.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
