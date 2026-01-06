<?php
// src/Controller/Admin/AdminController.php

namespace App\Controller\Admin;

use App\Entity\PersonalDetails;
use App\Entity\Education;
use App\Entity\WorkExperience;
use App\Entity\Skill;
use App\Entity\Project;
use App\Entity\Message;
use App\Form\PersonalDetailsType;
use App\Form\EducationType;
use App\Form\WorkExperienceType;
use App\Form\SkillType;
use App\Form\ProjectType;
use App\Form\ContactMessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Dashboard - Main Admin Page
     */
    #[Route('/dashboard-legacy', name: 'admin_dashboard_legacy')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get counts for dashboard stats
        $stats = [
            'personal_details' => $this->entityManager->getRepository(PersonalDetails::class)->count([]),
            'projects' => $this->entityManager->getRepository(Project::class)->count([]),
            'messages' => $this->entityManager->getRepository(Message::class)->count([]),
            'unread_messages' => $this->entityManager->getRepository(Message::class)->count(['isRead' => false]),
            'education' => $this->entityManager->getRepository(Education::class)->count([]),
            'work_experience' => $this->entityManager->getRepository(WorkExperience::class)->count([]),
            'skills' => $this->entityManager->getRepository(Skill::class)->count([]),
        ];

        // Add placeholder stats for template
        $stats['total_projects'] = $stats['projects'];
        $stats['published_projects'] = $stats['projects'];
        $stats['draft_projects'] = 0;
        $stats['total_skills'] = $stats['skills'];
        $stats['active_skills'] = $stats['skills'];
        $stats['total_messages'] = $stats['messages'];
        $stats['total_users'] = 1; // Only admin for now
        $stats['admin_users'] = 1;

        // Get recent messages
        $recentMessages = $this->entityManager->getRepository(Message::class)
            ->findBy([], ['createdAt' => 'DESC'], 5);

        // Get recent projects
        $recentProjects = $this->entityManager->getRepository(Project::class)
            ->findBy([], ['createdAt' => 'DESC'], 3);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentMessages' => $recentMessages,
            'recentProjects' => $recentProjects,
            'recent_users' => [], // Empty for now
        ]);
    }

    // ... REST OF YOUR EXISTING METHODS REMAIN THE SAME ...
    // Keep all your other methods (personalDetails, projects, messages, etc.)
}