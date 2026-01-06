<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Entity\Skill;
use App\Entity\Education;
use App\Entity\WorkExperience;
use App\Entity\Reference;
use App\Entity\ContactMessage;
use App\Entity\Admin;
use App\Form\EducationType;
use App\Form\WorkExperienceType;
use App\Form\ReferenceType;
use App\Service\ImageResizeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/admin")
 */
class DashboardController extends AbstractController
{
    private $em;
    private $imageResizeService;
    
    public function __construct(EntityManagerInterface $em, ImageResizeService $imageResizeService)
    {
        $this->em = $em;
        $this->imageResizeService = $imageResizeService;
    }
    
    /**
     * Main Admin Dashboard
     * 
     * @Route("/", name="admin_dashboard", methods={"GET"})
     */
    public function dashboard(): Response
    {
        // Check if user has ROLE_ADMIN
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $user = $this->getUser();
        
        // Get repositories
        $projectRepo = $this->em->getRepository(Project::class);
        $skillRepo = $this->em->getRepository(Skill::class);
        $messageRepo = $this->em->getRepository(ContactMessage::class);
        $adminRepo = $this->em->getRepository(Admin::class);
        
        // Statistics - with fallbacks in case entities don't exist
        $stats = [
            'total_projects' => 0,
            'published_projects' => 0,
            'total_skills' => 0,
            'active_skills' => 0,
            'total_messages' => 0,
            'unread_messages' => 0,
            'total_users' => 0,
            'admin_users' => 0,
        ];
        
        try {
            $stats['total_projects'] = $projectRepo->count([]);
            $stats['published_projects'] = $projectRepo->count(['status' => 'published']);
        } catch (\Exception $e) {
            // Project table might not exist
        }
        
        try {
            $stats['total_skills'] = $skillRepo->count([]);
            $stats['active_skills'] = $skillRepo->count(['isActive' => true]);
        } catch (\Exception $e) {
            // Skill table might not exist
        }
        
        try {
            $stats['total_messages'] = $messageRepo->count([]);
            $stats['unread_messages'] = $messageRepo->count(['isRead' => false]);
        } catch (\Exception $e) {
            // ContactMessage table might not exist
        }
        
        try {
            $stats['total_users'] = $adminRepo->count([]);
            // Count active admins as admin users
            $stats['admin_users'] = $adminRepo->count(['isActive' => true]);
        } catch (\Exception $e) {
            // Admin table might not exist
        }
        
        // Recent Activities - with try-catch for each
        $recentProjects = [];
        $recentMessages = [];
        $recentAdmins = [];
        
        try {
            $recentProjects = $projectRepo->findBy([], ['createdAt' => 'DESC'], 5);
        } catch (\Exception $e) {}
        
        try {
            $recentMessages = $messageRepo->findBy([], ['createdAt' => 'DESC'], 5);
        } catch (\Exception $e) {}
        
        try {
            $recentAdmins = $adminRepo->findBy([], ['createdAt' => 'DESC'], 5);
        } catch (\Exception $e) {}
        
        return $this->render('admin/dashboard/index.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'recent_projects' => $recentProjects,
            'recent_messages' => $recentMessages,
            'recent_users' => $recentAdmins,
            'page_title' => 'Dashboard',
        ]);
    }
    
    /**
     * Projects Management
     * 
     * @Route("/projects", name="admin_projects", methods={"GET"})
     */
    public function projects(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $projects = [];
        try {
            $projects = $this->em->getRepository(Project::class)->findBy([], ['createdAt' => 'DESC']);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Projects table not found or empty.');
        }
        
        return $this->render('admin/projects/index.html.twig', [
            'projects' => $projects,
            'page_title' => 'Manage Projects',
        ]);
    }
    
    /**
     * Skills Management
     * 
     * @Route("/skills", name="admin_skills", methods={"GET"})
     */
    public function skills(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $skills = [];
        try {
            $skills = $this->em->getRepository(Skill::class)->findBy([], ['name' => 'ASC']);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Skills table not found or empty.');
        }
        
        return $this->render('admin/skills/index.html.twig', [
            'skills' => $skills,
            'page_title' => 'Manage Skills',
        ]);
    }
    
    /**
     * Messages Management
     * 
     * @Route("/messages", name="admin_messages", methods={"GET"})
     */
    public function messages(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $messages = [];
        try {
            $messages = $this->em->getRepository(ContactMessage::class)->findBy([], ['createdAt' => 'DESC']);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Messages table not found or empty.');
        }
        
        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages,
            'page_title' => 'Contact Messages',
        ]);
    }
    
    /**
     * View Single Message
     * 
     * @Route("/messages/{id}", name="admin_message_show", methods={"GET"})
     */
    public function showMessage(int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $message = null;
        try {
            $message = $this->em->getRepository(ContactMessage::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Messages table not found.');
            return $this->redirectToRoute('admin_messages');
        }
        
        if (!$message) {
            $this->addFlash('error', 'Message not found!');
            return $this->redirectToRoute('admin_messages');
        }
        
        // Mark as read if not already
        try {
            // ContactMessage uses isIsRead() as generated by Doctrine boolean naming conventions
            if (method_exists($message, 'isIsRead') && !$message->isIsRead()) {
                if (method_exists($message, 'setIsRead')) {
                    $message->setIsRead(true);
                }
                if (method_exists($message, 'setReadAt')) {
                    $message->setReadAt(new \DateTime());
                }
                $this->em->flush();
            }
        } catch (\Exception $e) {
            // Ignore error
        }
        
        return $this->render('admin/messages/show.html.twig', [
            'message' => $message,
            'page_title' => 'Message Details',
        ]);
    }
    
    /**
     * Delete Message
     * 
     * @Route("/messages/{id}/delete", name="admin_message_delete", methods={"POST"})
     */
    public function deleteMessage(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $message = null;
        try {
            $message = $this->em->getRepository(ContactMessage::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Messages table not found.');
            return $this->redirectToRoute('admin_messages');
        }
        
        if (!$message) {
            $this->addFlash('error', 'Message not found!');
            return $this->redirectToRoute('admin_messages');
        }
        
        // Check CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-message-' . $id, $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token!');
            return $this->redirectToRoute('admin_messages');
        }
        
        try {
            $this->em->remove($message);
            $this->em->flush();
            $this->addFlash('success', 'Message deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting message: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_messages');
    }
    
    /**
     * Users Management
     * 
     * @Route("/users", name="admin_users", methods={"GET"})
     */
    public function users(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $admins = [];
        try {
            $admins = $this->em->getRepository(Admin::class)->findBy([], ['createdAt' => 'DESC']);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Admin users table not found or empty.');
        }
        
        return $this->render('admin/users/index.html.twig', [
            'users' => $admins,
            'page_title' => 'Manage Users',
        ]);
    }
    
    /**
     * Mark Message as Read/Unread (AJAX)
     * 
     * @Route("/messages/{id}/toggle-read", name="admin_message_toggle_read", methods={"POST"})
     */
    public function toggleMessageRead(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }
        
        try {
            $message = $this->em->getRepository(ContactMessage::class)->find($id);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Messages table not found']);
        }
        
        if (!$message) {
            return new JsonResponse(['success' => false, 'message' => 'Message not found']);
        }
        
        try {
            // Use isIsRead() which exists on ContactMessage entity
            if (method_exists($message, 'isIsRead') && method_exists($message, 'setIsRead')) {
                $currentStatus = $message->isIsRead();
                $message->setIsRead(!$currentStatus);

                if (method_exists($message, 'setReadAt')) {
                    $message->setReadAt($message->isIsRead() ? new \DateTime() : null);
                }

                $this->em->flush();

                return new JsonResponse([
                    'success' => true,
                    'is_read' => $message->isIsRead(),
                    'message' => 'Message updated successfully'
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Message entity does not have required methods'
                ]);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating message: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Admin Profile
     * 
     * @Route("/profile", name="admin_profile", methods={"GET"})
     */
    public function profile(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $user = $this->getUser();
        
        return $this->render('admin/profile/index.html.twig', [
            'user' => $user,
            'page_title' => 'My Profile',
        ]);
    }

    /**
     * Edit Admin Profile
     *
     * @Route("/profile/edit", name="admin_profile_edit", methods={"GET","POST"})
     */
    public function editProfile(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        /** @var Admin $user */
        $user = $this->getUser();

        $form = $this->createForm(\App\Form\AdminProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('admin/profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'page_title' => 'Edit Profile',
        ]);
    }
    
    /**
     * System Settings
     * 
     * @Route("/settings", name="admin_settings", methods={"GET"})
     */
    public function settings(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        return $this->render('admin/settings/index.html.twig', [
            'page_title' => 'System Settings',
        ]);
    }
    
    /**
     * Change Animation Setting
     * 
     * @Route("/change-animation/{type}", name="admin_change_animation", methods={"GET", "POST"})
     */
    public function changeAnimation(string $type): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }
        
        // Valid animation types
        $validTypes = ['enabled', 'disabled', 'reduce'];
        
        if (!in_array($type, $validTypes)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid animation type']);
        }
        
        // Store preference in session
        $session = $this->get('session');
        $session->set('animation_preference', $type);
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Animation preference updated',
            'type' => $type
        ]);
    }
    
    /**
     * New Project Form
     * 
     * @Route("/projects/new", name="admin_project_new", methods={"GET", "POST"})
     */
    public function newProject(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $project = new Project();
        $form = $this->createForm(\App\Form\ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle screenshot upload with resizing
            $screenshotFile = $form->get('screenshotPath')->getData();
            if ($screenshotFile) {
                try {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/projects';
                    $resizedFilename = $this->imageResizeService->resizeAndSave($screenshotFile, $uploadDir);
                    $project->setScreenshotPath($resizedFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error uploading screenshot: ' . $e->getMessage());
                    return $this->render('admin/projects/new.html.twig', [
                        'form' => $form->createView(),
                        'page_title' => 'Create New Project',
                    ]);
                }
            }

            $project->setCreatedAt(new \DateTime());
            $project->setUpdatedAt(null);

            $this->em->persist($project);
            $this->em->flush();

            $this->addFlash('success', 'Project created successfully!');
            return $this->redirectToRoute('admin_projects');
        }

        return $this->render('admin/projects/new.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Create New Project',
        ]);
    }

    /**
     * Edit Project
     * 
     * @Route("/projects/{id}/edit", name="admin_project_edit", methods={"GET", "POST"})
     */
    public function editProject(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $project = null;
        try {
            $project = $this->em->getRepository(Project::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Projects table not found.');
            return $this->redirectToRoute('admin_projects');
        }

        if (!$project) {
            $this->addFlash('error', 'Project not found!');
            return $this->redirectToRoute('admin_projects');
        }

        $form = $this->createForm(\App\Form\ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle screenshot upload with resizing
            $screenshotFile = $form->get('screenshotPath')->getData();
            if ($screenshotFile) {
                // Delete old screenshot if it exists
                if ($project->getScreenshotPath()) {
                    $oldFile = $this->getParameter('kernel.project_dir') . '/public/uploads/projects/' . $project->getScreenshotPath();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                try {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/projects';
                    $resizedFilename = $this->imageResizeService->resizeAndSave($screenshotFile, $uploadDir);
                    $project->setScreenshotPath($resizedFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error uploading screenshot: ' . $e->getMessage());
                    return $this->render('admin/projects/edit.html.twig', [
                        'form' => $form->createView(),
                        'project' => $project,
                        'page_title' => 'Edit Project',
                    ]);
                }
            }

            $project->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->addFlash('success', 'Project updated successfully!');
            return $this->redirectToRoute('admin_projects');
        }

        return $this->render('admin/projects/edit.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'page_title' => 'Edit Project',
        ]);
    }
    
    /**
     * Toggle Project Publish Status
     *
     * @Route("/projects/{id}/toggle-status", name="admin_project_toggle_status", methods={"POST"})
     */
    public function toggleProjectStatus(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $project = null;
        try {
            $project = $this->em->getRepository(Project::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Projects table not found.');
            return $this->redirectToRoute('admin_projects');
        }

        if (!$project) {
            $this->addFlash('error', 'Project not found!');
            return $this->redirectToRoute('admin_projects');
        }

        // Toggle published/draft
        $project->setIsPublished(!$project->isPublished());
        $project->setStatus($project->isPublished() ? 'published' : 'draft');
        $project->setUpdatedAt(new \DateTime());

        try {
            $this->em->flush();
            $this->addFlash('success', 'Project status updated.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error updating project status: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_projects');
    }

    /**
     * Delete Project
     * 
     * @Route("/projects/{id}/delete", name="admin_project_delete", methods={"POST"})
     */
    public function deleteProject(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $project = null;
        try {
            $project = $this->em->getRepository(Project::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Projects table not found.');
            return $this->redirectToRoute('admin_projects');
        }
        
        if (!$project) {
            $this->addFlash('error', 'Project not found!');
            return $this->redirectToRoute('admin_projects');
        }
        
        // Check CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-project-' . $id, $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token!');
            return $this->redirectToRoute('admin_projects');
        }
        
        try {
            $this->em->remove($project);
            $this->em->flush();
            $this->addFlash('success', 'Project deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting project: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_projects');
    }
    
    /**
     * New Skill Form
     * 
     * @Route("/skills/new", name="admin_skill_new", methods={"GET", "POST"})
     */
    public function newSkill(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $skill = new Skill();
        $form = $this->createForm(\App\Form\SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($skill);
            $this->em->flush();

            $this->addFlash('success', 'Skill created successfully!');
            return $this->redirectToRoute('admin_skills');
        }

        return $this->render('admin/skills/new.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Create New Skill',
        ]);
    }

    /**
     * Edit Skill
     * 
     * @Route("/skills/{id}/edit", name="admin_skill_edit", methods={"GET", "POST"})
     */
    public function editSkill(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $skill = null;
        try {
            $skill = $this->em->getRepository(Skill::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Skills table not found.');
            return $this->redirectToRoute('admin_skills');
        }

        if (!$skill) {
            $this->addFlash('error', 'Skill not found!');
            return $this->redirectToRoute('admin_skills');
        }

        $form = $this->createForm(\App\Form\SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Skill updated successfully!');
            return $this->redirectToRoute('admin_skills');
        }

        return $this->render('admin/skills/edit.html.twig', [
            'form' => $form->createView(),
            'skill' => $skill,
            'page_title' => 'Edit Skill',
        ]);
    }
    
    /**
     * Delete Skill
     * 
     * @Route("/skills/{id}/delete", name="admin_skill_delete", methods={"POST"})
     */
    public function deleteSkill(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }
        
        $skill = null;
        try {
            $skill = $this->em->getRepository(Skill::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Skills table not found.');
            return $this->redirectToRoute('admin_skills');
        }
        
        if (!$skill) {
            $this->addFlash('error', 'Skill not found!');
            return $this->redirectToRoute('admin_skills');
        }
        
        // Check CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-skill-' . $id, $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token!');
            return $this->redirectToRoute('admin_skills');
        }
        
        try {
            $this->em->remove($skill);
            $this->em->flush();
            $this->addFlash('success', 'Skill deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting skill: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_skills');
    }

    /**
     * Personal Details Management (List)
     *
     * @Route("/personal-details", name="admin_personal_details", methods={"GET"})
     */
    public function personalDetails(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $details = [];
        try {
            $details = $this->em->getRepository(\App\Entity\PersonalDetails::class)->findBy([], ['updatedAt' => 'DESC']);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Personal details table not found or empty.');
        }

        return $this->render('admin/personal_details/index.html.twig', [
            'details' => $details,
            'page_title' => 'Manage Personal Details',
        ]);
    }

    /**
     * New Personal Details
     *
     * @Route("/personal-details/new", name="admin_personal_details_new", methods={"GET","POST"})
     */
    public function newPersonalDetails(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $personal = new \App\Entity\PersonalDetails();
        $form = $this->createForm(\App\Form\PersonalDetailsType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file uploads
            $cvFile = $form->get('cvFile')->getData();
            if ($cvFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
                if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0755, true); }
                $cvFilename = uniqid('cv_') . '.' . $cvFile->guessExtension();
                $cvFile->move($uploadsDir, $cvFilename);
                $personal->setCvPath($cvFilename);
            }

            $profileFile = $form->get('profileImageFile')->getData();
            if ($profileFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profile';
                if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0755, true); }
                $imgFilename = uniqid('profile_') . '.' . $profileFile->guessExtension();
                $profileFile->move($uploadsDir, $imgFilename);
                $personal->setProfileImage($imgFilename);
            }

            $this->em->persist($personal);
            $this->em->flush();

            $this->addFlash('success', 'Personal details saved successfully!');
            return $this->redirectToRoute('admin_personal_details');
        }

        return $this->render('admin/personal_details/new.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Create Personal Details',
        ]);
    }

    /**
     * Edit Personal Details
     *
     * @Route("/personal-details/{id}/edit", name="admin_personal_details_edit", methods={"GET","POST"})
     */
    public function editPersonalDetails(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $personal = null;
        try {
            $personal = $this->em->getRepository(\App\Entity\PersonalDetails::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Personal details table not found.');
            return $this->redirectToRoute('admin_personal_details');
        }

        if (!$personal) {
            $this->addFlash('error', 'Personal details not found!');
            return $this->redirectToRoute('admin_personal_details');
        }

        $form = $this->createForm(\App\Form\PersonalDetailsType::class, $personal, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Personal details updated successfully!');
            return $this->redirectToRoute('admin_personal_details');
        }

        return $this->render('admin/personal_details/edit.html.twig', [
            'form' => $form->createView(),
            'personal' => $personal,
            'page_title' => 'Edit Personal Details',
        ]);
    }

    /**
     * Delete Personal Details
     *
     * @Route("/personal-details/{id}/delete", name="admin_personal_details_delete", methods={"POST"})
     */
    public function deletePersonalDetails(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $personal = null;
        try {
            $personal = $this->em->getRepository(\App\Entity\PersonalDetails::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Personal details table not found.');
            return $this->redirectToRoute('admin_personal_details');
        }

        if (!$personal) {
            $this->addFlash('error', 'Personal details not found!');
            return $this->redirectToRoute('admin_personal_details');
        }

        // Check CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-personal-' . $id, $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token!');
            return $this->redirectToRoute('admin_personal_details');
        }

        try {
            $this->em->remove($personal);
            $this->em->flush();
            $this->addFlash('success', 'Personal details deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting personal details: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_personal_details');
    }

    /**
     * Education Management
     * 
     * @Route("/education", name="admin_education", methods={"GET"})
     */
    public function education(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $educations = [];
        try {
            $educations = $this->em->getRepository(Education::class)->findBy([], ['startDate' => 'DESC']);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Education table not found or empty.');
        }

        return $this->render('admin/education/index.html.twig', [
            'educations' => $educations,
            'page_title' => 'Manage Education',
        ]);
    }

    /**
     * New Education Form
     * 
     * @Route("/education/new", name="admin_education_new", methods={"GET", "POST"})
     */
    public function newEducation(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $education = new Education();
        $form = $this->createForm(EducationType::class, $education);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($education);
            $this->em->flush();

            $this->addFlash('success', 'Education entry created successfully!');
            return $this->redirectToRoute('admin_education');
        }

        return $this->render('admin/education/new.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Create Education',
        ]);
    }

    /**
     * Edit Education
     * 
     * @Route("/education/{id}/edit", name="admin_education_edit", methods={"GET", "POST"})
     */
    public function editEducation(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $education = null;
        try {
            $education = $this->em->getRepository(Education::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Education table not found.');
            return $this->redirectToRoute('admin_education');
        }

        if (!$education) {
            $this->addFlash('error', 'Education entry not found!');
            return $this->redirectToRoute('admin_education');
        }

        $form = $this->createForm(EducationType::class, $education);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $education->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->addFlash('success', 'Education updated successfully!');
            return $this->redirectToRoute('admin_education');
        }

        return $this->render('admin/education/edit.html.twig', [
            'form' => $form->createView(),
            'education' => $education,
            'page_title' => 'Edit Education',
        ]);
    }

    /**
     * Delete Education
     * 
     * @Route("/education/{id}/delete", name="admin_education_delete", methods={"POST"})
     */
    public function deleteEducation(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $education = null;
        try {
            $education = $this->em->getRepository(Education::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Education table not found.');
            return $this->redirectToRoute('admin_education');
        }

        if (!$education) {
            $this->addFlash('error', 'Education entry not found!');
            return $this->redirectToRoute('admin_education');
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-education-' . $id, $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token!');
            return $this->redirectToRoute('admin_education');
        }

        try {
            $this->em->remove($education);
            $this->em->flush();
            $this->addFlash('success', 'Education entry deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting education: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_education');
    }

    /**
     * Work Experience Management
     * 
     * @Route("/work-experience", name="admin_work_experience", methods={"GET"})
     */
    public function workExperience(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $work = [];
        try {
            $work = $this->em->getRepository(WorkExperience::class)->findBy([], ['startDate' => 'DESC']);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Work experience table not found or empty.');
        }

        return $this->render('admin/work_experience/index.html.twig', [
            'workExperience' => $work,
            'page_title' => 'Manage Work Experience',
        ]);
    }

    /**
     * New Work Experience
     * 
     * @Route("/work-experience/new", name="admin_work_experience_new", methods={"GET", "POST"})
     */
    public function newWorkExperience(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $work = new WorkExperience();
        $form = $this->createForm(WorkExperienceType::class, $work);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($work);
            $this->em->flush();

            $this->addFlash('success', 'Work experience created successfully!');
            return $this->redirectToRoute('admin_work_experience');
        }

        return $this->render('admin/work_experience/new.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Create Work Experience',
        ]);
    }

    /**
     * Edit Work Experience
     * 
     * @Route("/work-experience/{id}/edit", name="admin_work_experience_edit", methods={"GET", "POST"})
     */
    public function editWorkExperience(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $work = null;
        try {
            $work = $this->em->getRepository(WorkExperience::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Work experience table not found.');
            return $this->redirectToRoute('admin_work_experience');
        }

        if (!$work) {
            $this->addFlash('error', 'Work experience not found!');
            return $this->redirectToRoute('admin_work_experience');
        }

        $form = $this->createForm(WorkExperienceType::class, $work);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $work->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->addFlash('success', 'Work experience updated successfully!');
            return $this->redirectToRoute('admin_work_experience');
        }

        return $this->render('admin/work_experience/edit.html.twig', [
            'form' => $form->createView(),
            'work' => $work,
            'page_title' => 'Edit Work Experience',
        ]);
    }

    /**
     * Delete Work Experience
     * 
     * @Route("/work-experience/{id}/delete", name="admin_work_experience_delete", methods={"POST"})
     */
    public function deleteWorkExperience(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $work = null;
        try {
            $work = $this->em->getRepository(WorkExperience::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Work experience table not found.');
            return $this->redirectToRoute('admin_work_experience');
        }

        if (!$work) {
            $this->addFlash('error', 'Work experience not found!');
            return $this->redirectToRoute('admin_work_experience');
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-work-' . $id, $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token!');
            return $this->redirectToRoute('admin_work_experience');
        }

        try {
            $this->em->remove($work);
            $this->em->flush();
            $this->addFlash('success', 'Work experience deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting work experience: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_work_experience');
    }

    /**
     * References List
     * 
     * @Route("/references", name="admin_references", methods={"GET"})
     */
    public function references(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $references = [];
        try {
            $references = $this->em->getRepository(Reference::class)->findBy([], ['displayOrder' => 'ASC']);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'References table not found or empty.');
        }

        return $this->render('admin/references/index.html.twig', [
            'references' => $references,
            'page_title' => 'Manage References',
        ]);
    }

    /**
     * New Reference
     * 
     * @Route("/references/new", name="admin_reference_new", methods={"GET", "POST"})
     */
    public function newReference(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $reference = new Reference();
        $form = $this->createForm(ReferenceType::class, $reference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reference->setCreatedAt(new \DateTime());

            $this->em->persist($reference);
            $this->em->flush();

            $this->addFlash('success', 'Reference added successfully!');
            return $this->redirectToRoute('admin_references');
        }

        return $this->render('admin/references/new.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Add New Reference',
        ]);
    }

    /**
     * Edit Reference
     * 
     * @Route("/references/{id}/edit", name="admin_reference_edit", methods={"GET", "POST"})
     */
    public function editReference(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $reference = null;
        try {
            $reference = $this->em->getRepository(Reference::class)->find($id);
        } catch (\Exception $e) {
            $this->addFlash('error', 'References table not found.');
            return $this->redirectToRoute('admin_references');
        }

        if (!$reference) {
            $this->addFlash('error', 'Reference not found!');
            return $this->redirectToRoute('admin_references');
        }

        $form = $this->createForm(ReferenceType::class, $reference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reference->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->addFlash('success', 'Reference updated successfully!');
            return $this->redirectToRoute('admin_references');
        }

        return $this->render('admin/references/edit.html.twig', [
            'form' => $form->createView(),
            'reference' => $reference,
            'page_title' => 'Edit Reference',
        ]);
    }

    /**
     * Delete Reference
     *
     * @Route("/references/{id}/delete", name="admin_reference_delete", methods={"POST"})
     */
    public function deleteReference(Request $request, int $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. Admin privileges required.');
        }

        $reference = $this->em->getRepository(Reference::class)->find($id);

        if (!$reference) {
            $this->addFlash('error', 'Reference not found!');
            return $this->redirectToRoute('admin_references');
        }

        try {
            $this->em->remove($reference);
            $this->em->flush();
            $this->addFlash('success', 'Reference deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting reference: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_references');
    }
}
