<?php
// src/Controller/PortfolioController.php

namespace App\Controller;

use App\Entity\PersonalDetails;
use App\Entity\Project;
use App\Entity\ContactMessage;
use App\Entity\Education;
use App\Entity\WorkExperience;
use App\Entity\Reference;
use App\Entity\Skill;
use App\Form\ContactMessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PortfolioController extends AbstractController
{
    private $entityManager;
    private $mailer;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager, 
        MailerInterface $mailer = null,
        LoggerInterface $logger = null
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="app_home")
     */
    public function home(): Response
    {
        try {
            // Get active personal details with try-catch fallback
            $personalDetails = $this->getPersonalDetails();
            
            // Get education history (guard if entity is missing)
            $education = [];
            if (class_exists(Education::class)) {
                $education = $this->entityManager
                    ->getRepository(Education::class)
                    ->findBy(['isActive' => true], ['endDate' => 'DESC']);
            }

            // Get work experience (guard if entity is missing)
            $workExperience = [];
            if (class_exists(WorkExperience::class)) {
                $workExperience = $this->entityManager
                    ->getRepository(WorkExperience::class)
                    ->findBy(['isActive' => true], ['endDate' => 'DESC']);
            }

            // Get skills
            $skills = $this->entityManager
                ->getRepository(Skill::class)
                ->findBy(['isActive' => true], ['percentage' => 'DESC']);

            // Get all published projects count
            $projectsCount = $this->entityManager
                ->getRepository(Project::class)
                ->count(['isPublished' => true]);

            // Get featured projects (max 3)
            $featuredProjects = $this->entityManager
                ->getRepository(Project::class)
                ->findBy(['isPublished' => true], ['createdAt' => 'DESC'], 3);

            // Calculate total months of experience from work history
            $totalMonths = 0;
            if ($workExperience) {
                $allWorkExperience = $this->entityManager
                    ->getRepository(WorkExperience::class)
                    ->findBy(['isActive' => true]);
                foreach ($allWorkExperience as $job) {
                    if ($job->getStartDate() && $job->getEndDate()) {
                        $start = $job->getStartDate();
                        $end = $job->getEndDate();
                        $interval = $start->diff($end);
                        $totalMonths += ($interval->y * 12) + $interval->m;
                    }
                }
            }
            // Convert to years (round up if more than 6 months)
            $yearsOfExperience = $totalMonths >= 6 ? max(1, (int) round($totalMonths / 12)) : 0;

            // Total skills count: 7 technical + 6 soft = 13
            $totalSkillsCount = 13;

            return $this->render('portfolio/home.html.twig', [
                'personalDetails' => $personalDetails,
                'education' => $education,
                'workExperience' => $workExperience,
                'skills' => $skills,
                'featuredProjects' => $featuredProjects,
                'projectsCount' => $projectsCount,
                'yearsOfExperience' => $yearsOfExperience,
                'totalSkillsCount' => $totalSkillsCount,
            ]);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Home page error: ' . $e->getMessage());
            }
            
            return $this->render('portfolio/home.html.twig', [
                'personalDetails' => null,
                'education' => [],
                'workExperience' => [],
                'skills' => [],
                'featuredProjects' => [],
                'yearsOfExperience' => 3,
            ]);
        }
    }

    /**
     * @Route("/about", name="app_about")
     */
    public function about(): Response
    {
        try {
            $personalDetails = $this->getPersonalDetails();
            
            $skills = $this->entityManager
                ->getRepository(Skill::class)
                ->findBy([], ['percentage' => 'DESC']);

            // Get education records
            $education = [];
            if (class_exists(Education::class)) {
                $education = $this->entityManager
                    ->getRepository(Education::class)
                    ->findBy(['isActive' => true], ['endDate' => 'DESC']);
            }

            // Get work experience records
            $workExperience = [];
            if (class_exists(WorkExperience::class)) {
                $workExperience = $this->entityManager
                    ->getRepository(WorkExperience::class)
                    ->findBy(['isActive' => true], ['endDate' => 'DESC']);
            }

            return $this->render('portfolio/about.html.twig', [
                'personalDetails' => $personalDetails,
                'skills' => $skills,
                'education' => $education,
                'workExperience' => $workExperience,
            ]);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('About page error: ' . $e->getMessage());
            }
            
            return $this->render('portfolio/about.html.twig', [
                'personalDetails' => null,
                'skills' => [],
                'education' => [],
                'workExperience' => [],
            ]);
        }
    }

    /**
     * @Route("/projects", name="app_projects")
     */
    public function projects(): Response
    {
        try {
            $repo = $this->entityManager->getRepository(Project::class);

            // Prefer repository helper that enforces both status and published flag
            if (method_exists($repo, 'findPublished')) {
                $projects = $repo->findPublished();
            } else {
                // Fallback to boolean published flag if helper not present
                $projects = $repo->findBy(['isPublished' => true], ['createdAt' => 'DESC']);
            }

            // Group projects by category for filtering
            $categories = [];
            foreach ($projects as $project) {
                if (method_exists($project, 'getCategory') && $project->getCategory() && !in_array($project->getCategory(), $categories)) {
                    $categories[] = $project->getCategory();
                }
            }

            return $this->render('portfolio/projects.html.twig', [
                'projects' => $projects,
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Projects page error: ' . $e->getMessage());
            }
            
            return $this->render('portfolio/projects.html.twig', [
                'projects' => [],
                'categories' => [],
            ]);
        }
    }

    /**
     * @Route("/project/{id}", name="app_project_detail")
     */
    public function projectDetail(int $id): Response
    {
        try {
            $project = $this->entityManager
                ->getRepository(Project::class)
                ->find($id);

            if (!$project) {
                throw $this->createNotFoundException('Project not found');
            }

            // Get related projects (same category)
            $relatedProjects = $this->entityManager
                ->getRepository(Project::class)
                ->findBy([
                    'category' => $project->getCategory(),
                    'isPublished' => true,
                ], ['createdAt' => 'DESC'], 3);

            // Remove current project from related projects
            $relatedProjects = array_filter($relatedProjects, function($p) use ($project) {
                return $p->getId() !== $project->getId();
            });

            return $this->render('portfolio/project_detail.html.twig', [
                'project' => $project,
                'relatedProjects' => array_slice($relatedProjects, 0, 2),
            ]);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Project not found');
        }
    }

    /**
     * @Route("/contact", name="app_contact")
     */
    public function contact(Request $request): Response
    {
        // Check if ContactMessage entity exists
        if (class_exists(ContactMessage::class)) {
            $message = new ContactMessage();
            $form = $this->createForm(ContactMessageType::class, $message);
        } else {
            // Create simple form without Message entity
            $form = $this->createFormBuilder()
                ->add('name', TextType::class, [
                    'label' => 'Your Name',
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control form-control-lg',
                        'placeholder' => 'Enter your full name'
                    ]
                ])
                ->add('email', EmailType::class, [
                    'label' => 'Your Email',
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control form-control-lg',
                        'placeholder' => 'Enter your email address'
                    ]
                ])
                ->add('subject', TextType::class, [
                    'label' => 'Subject',
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control form-control-lg',
                        'placeholder' => 'Message subject'
                    ]
                ])
                ->add('message', TextareaType::class, [
                    'label' => 'Your Message',
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control form-control-lg',
                        'placeholder' => 'Type your message here...',
                        'rows' => 6
                    ]
                ])
                ->add('send', SubmitType::class, [
                    'label' => 'Send Message',
                    'attr' => [
                        'class' => 'btn btn-primary-custom btn-lg px-5 py-3',
                        'style' => 'font-weight: 600;'
                    ]
                ])
                ->getForm();
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($this->logger) {
                $this->logger->info('📨 Form submitted - checking validity');
                if ($form->isValid()) {
                    $data = $form->getData();
                    $this->logger->info('✅ Form IS VALID. Data: name=' . $data->getName() . ', email=' . $data->getEmail() . ', subject=' . $data->getSubject());
                } else {
                    $this->logger->warning('❌ Form validation FAILED');
                    foreach ($form->getErrors(true, true) as $error) {
                        $this->logger->error('Error: ' . $error->getMessage() . ' (' . $error->getOrigin()->getName() . ')');
                    }
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($this->logger) {
                    $this->logger->info('=== CONTACT FORM SUBMITTED & VALID ===');
                }
                
                $message = $form->getData();
                
                if ($this->logger) {
                    $this->logger->info('Message data: ' . $message->getName() . ' - ' . $message->getEmail());
                }
                
                // Save to database
                $this->entityManager->persist($message);
                $this->entityManager->flush();
                
                if ($this->logger) {
                    $this->logger->info('Message saved to database with ID: ' . $message->getId());
                }

                // Send email notification
                try {
                    $this->sendContactEmail($message);
                    if ($this->logger) {
                        $this->logger->info('Email sent successfully!');
                    }
                } catch (\Exception $emailError) {
                    if ($this->logger) {
                        $this->logger->error('Email failed: ' . $emailError->getMessage());
                    }
                }

                $this->addFlash('success', '✅ Thank you! Your message has been sent successfully. I will contact you soon!');
                
                return $this->redirectToRoute('app_contact');

            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error('❌ Contact form error: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
                }
                
                $this->addFlash('error', '❌ Sorry, there was an issue sending your message. Please try again.');
                return $this->redirectToRoute('app_contact');
            }
        }

        return $this->render('portfolio/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/cv", name="app_cv")
     */
    public function cv(): Response
    {
        try {
            $personalDetails = $this->getPersonalDetails();

            $education = [];
            if (class_exists(Education::class)) {
                $education = $this->entityManager
                    ->getRepository(Education::class)
                    ->findBy(['isActive' => true], ['endDate' => 'DESC']);
            }

            $workExperience = [];
            if (class_exists(WorkExperience::class)) {
                $workExperience = $this->entityManager
                    ->getRepository(WorkExperience::class)
                    ->findBy(['isActive' => true], ['endDate' => 'DESC']);
            }

            $references = [];
            if (class_exists(Reference::class)) {
                $references = $this->entityManager
                    ->getRepository(Reference::class)
                    ->findBy(['isActive' => true], ['displayOrder' => 'ASC']);
            }

            $skills = $this->entityManager
                ->getRepository(Skill::class)
                ->findBy(['isActive' => true], ['percentage' => 'DESC']);

            // Get all projects count
            $projectsCount = $this->entityManager
                ->getRepository(Project::class)
                ->count(['isPublished' => true]);

            // Get skills count
            $skillsCount = count($skills);

            // Calculate total months of experience from work history
            $totalMonths = 0;
            if ($workExperience) {
                $allWorkExperience = $this->entityManager
                    ->getRepository(WorkExperience::class)
                    ->findBy(['isActive' => true]);
                foreach ($allWorkExperience as $job) {
                    if ($job->getStartDate() && $job->getEndDate()) {
                        $start = $job->getStartDate();
                        $end = $job->getEndDate();
                        $interval = $start->diff($end);
                        $totalMonths += ($interval->y * 12) + $interval->m;
                    }
                }
            }
            // Convert to years (round up if more than 6 months)
            $yearsOfExperience = $totalMonths >= 6 ? max(1, (int) round($totalMonths / 12)) : 0;

            return $this->render('portfolio/cv.html.twig', [
                'personalDetails' => $personalDetails,
                'education' => $education,
                'workExperience' => $workExperience,
                'references' => $references,
                'skills' => $skills,
                'projectsCount' => $projectsCount,
                'skillsCount' => $skillsCount,
                'yearsOfExperience' => $yearsOfExperience,
            ]);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('CV page error: ' . $e->getMessage());
            }
            
            return $this->render('portfolio/cv.html.twig', [
                'personalDetails' => null,
                'education' => [],
                'workExperience' => [],
                'references' => [],
                'skills' => [],
            ]);
        }
    }

    /**
     * @Route("/download-cv", name="download_cv")
     */
    public function downloadCV(): Response
    {
        try {
            $personalDetails = $this->getPersonalDetails();

            if (!$personalDetails || !$personalDetails->getCvPath()) {
                $this->addFlash('error', 'CV file is not available.');
                return $this->redirectToRoute('app_cv');
            }

            // Get project directory
            $projectDir = $this->getParameter('kernel.project_dir');
            
            // Try different possible locations
            $possiblePaths = [
                $projectDir . '/public/uploads/cv/' . $personalDetails->getCvPath(),
                $projectDir . '/public/' . $personalDetails->getCvPath(),
                $projectDir . '/uploads/cv/' . $personalDetails->getCvPath(),
                $personalDetails->getCvPath(),
            ];

            $cvPath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $cvPath = $path;
                    break;
                }
            }
            
            if (!$cvPath || !file_exists($cvPath)) {
                $this->addFlash('error', 'CV file not found.');
                return $this->redirectToRoute('app_cv');
            }

            // Create response for file download
            $response = new BinaryFileResponse($cvPath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'Noely_Bernard_Mnyambwa_CV.pdf'
            );

            return $response;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('CV download error: ' . $e->getMessage());
            }
            
            $this->addFlash('error', 'CV download is currently unavailable.');
            return $this->redirectToRoute('app_cv');
        }
    }

    /**
     * @Route("/services", name="app_services")
     */
    public function services(): Response
    {
        try {
            $personalDetails = $this->getPersonalDetails();

            $skills = $this->entityManager
                ->getRepository(Skill::class)
                ->findBy([], ['percentage' => 'DESC']);

            return $this->render('portfolio/services.html.twig', [
                'personalDetails' => $personalDetails,
                'skills' => $skills,
            ]);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Services page error: ' . $e->getMessage());
            }
            
            return $this->render('portfolio/services.html.twig', [
                'personalDetails' => null,
                'skills' => [],
            ]);
        }
    }

    /**
     * @Route("/testimonials", name="app_testimonials")
     */
    public function testimonials(): Response
    {
        try {
            $personalDetails = $this->getPersonalDetails();

            return $this->render('portfolio/testimonials.html.twig', [
                'personalDetails' => $personalDetails,
            ]);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Testimonials page error: ' . $e->getMessage());
            }
            
            return $this->render('portfolio/testimonials.html.twig', [
                'personalDetails' => null,
            ]);
        }
    }

    /**
     * @Route("/blog", name="app_blog")
     */
    public function blog(): Response
    {
        return $this->render('portfolio/blog.html.twig');
    }

    /**
     * @Route("/privacy-policy", name="app_privacy_policy")
     */
    public function privacyPolicy(): Response
    {
        return $this->render('portfolio/privacy_policy.html.twig');
    }

    /**
     * @Route("/terms-of-service", name="app_terms_of_service")
     */
    public function termsOfService(): Response
    {
        return $this->render('portfolio/terms_of_service.html.twig');
    }

    /**
     * @Route("/debug", name="app_debug")
     */
    public function debug(): Response
    {
        if (($_ENV['APP_ENV'] ?? 'dev') === 'prod') {
            throw $this->createNotFoundException();
        }

        // Get all counts for debugging
        $counts = [];
        $entities = [
            'PersonalDetails' => 'personal_details',
            'Project' => 'projects',
            'Message' => 'messages',
            'Education' => 'education',
            'WorkExperience' => 'work_experience',
            'Skill' => 'skills'
        ];

        foreach ($entities as $entityClass => $key) {
            try {
                if (class_exists("App\\Entity\\{$entityClass}")) {
                    $counts[$key] = $this->entityManager->getRepository("App\\Entity\\{$entityClass}")->count([]);
                } else {
                    $counts[$key] = 'Entity not found';
                }
            } catch (\Exception $e) {
                $counts[$key] = 'Error: ' . $e->getMessage();
            }
        }

        return $this->render('debug.html.twig', [
            'counts' => $counts,
            'environment' => $_ENV['APP_ENV'] ?? 'dev',
            'debug' => $_ENV['APP_DEBUG'] ?? false,
        ]);
    }

    /**
     * @Route("/api/contact", name="app_api_contact", methods={"POST"})
     */
    public function apiContact(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['success' => false, 'error' => 'Invalid data'], 400);
            }

            // Try to use Message entity if it exists
            if (class_exists(Message::class)) {
                $message = new Message();
                $message->setName($data['name'] ?? '');
                $message->setEmail($data['email'] ?? '');
                $message->setSubject($data['subject'] ?? 'No Subject');
                $message->setMessage($data['message'] ?? '');
                $message->setCreatedAt(new \DateTime());

                $this->entityManager->persist($message);
                $this->entityManager->flush();

                // Send email notification
                $this->sendContactEmail($message);
            } else {
                // Just send email
                $this->sendSimpleContactEmail($data);
            }

            return $this->json([
                'success' => true,
                'message' => 'Message sent successfully'
            ]);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('API contact error: ' . $e->getMessage());
            }
            
            return $this->json([
                'success' => false,
                'error' => 'Failed to send message'
            ], 500);
        }
    }

    /**
     * Send contact email notification
     */
    private function sendContactEmail($message): void
    {
        try {
            if (!$this->mailer) {
                if ($this->logger) {
                    $this->logger->warning('Mailer not configured');
                }
                throw new \Exception('Mailer not configured');
            }

            $htmlContent = '
            <html>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                    <h2 style="color: #2c3e50; border-bottom: 2px solid #1abc9c; padding-bottom: 10px;">📬 New Message from Your Portfolio</h2>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                        <h3 style="color: #1abc9c; margin-top: 0;">Sender Information:</h3>
                        <p><strong>👤 Name:</strong> ' . htmlspecialchars($message->getName()) . '</p>
                        <p><strong>📧 Email:</strong> <a href="mailto:' . htmlspecialchars($message->getEmail()) . '">' . htmlspecialchars($message->getEmail()) . '</a></p>
                        <p><strong>📌 Subject:</strong> ' . htmlspecialchars($message->getSubject()) . '</p>
                    </div>
                    
                    <div style="background: #fff; padding: 15px; border-left: 4px solid #1abc9c;">
                        <h3 style="color: #2c3e50;">💬 Message:</h3>
                        <p style="white-space: pre-wrap;">' . htmlspecialchars($message->getMessage()) . '</p>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
                        <p>This message was sent from your portfolio contact form.</p>
                        <p>To reply, simply respond to this email or click the email address above.</p>
                    </div>
                </div>
            </body>
            </html>';

            $email = (new Email())
                ->from(new \Symfony\Component\Mime\Address('mnyambwanoel123@gmail.com', 'Portfolio Contact Form'))
                ->to('mnyambwanoel123@gmail.com')
                ->replyTo(new \Symfony\Component\Mime\Address($message->getEmail(), $message->getName()))
                ->subject('Portfolio Contact: ' . $message->getSubject())
                ->priority(Email::PRIORITY_HIGH)
                ->html($htmlContent)
                ->text(
                    "=== NEW MESSAGE FROM YOUR PORTFOLIO ===\n\n" .
                    "SENDER INFORMATION:\n" .
                    "-------------------\n" .
                    "Name: " . $message->getName() . "\n" .
                    "Email: " . $message->getEmail() . "\n" .
                    "Subject: " . $message->getSubject() . "\n\n" .
                    "MESSAGE:\n" .
                    "--------\n" .
                    $message->getMessage() . "\n\n" .
                    "-------------------\n" .
                    "Reply to this email to respond directly to the sender."
                );

            $this->mailer->send($email);
            
            if ($this->logger) {
                $this->logger->info('Email sent successfully to mnyambwanoel123@gmail.com');
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Email sending failed: ' . $e->getMessage());
            }
            throw $e; // Re-throw to let caller handle it
        }
    }

    /**
     * Send simple contact email (without Message entity)
     */
    private function sendSimpleContactEmail(array $data): void
    {
        try {
            if (!$this->mailer) {
                return;
            }

            $htmlContent = '
            <html>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                    <h2 style="color: #2c3e50; border-bottom: 2px solid #1abc9c; padding-bottom: 10px;">📬 New Message from Your Portfolio</h2>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                        <h3 style="color: #1abc9c; margin-top: 0;">Sender Information:</h3>
                        <p><strong>👤 Name:</strong> ' . htmlspecialchars($data['name']) . '</p>
                        <p><strong>📧 Email:</strong> <a href="mailto:' . htmlspecialchars($data['email']) . '">' . htmlspecialchars($data['email']) . '</a></p>
                        <p><strong>📌 Subject:</strong> ' . htmlspecialchars($data['subject'] ?? 'No Subject') . '</p>
                    </div>
                    
                    <div style="background: #fff; padding: 15px; border-left: 4px solid #1abc9c;">
                        <h3 style="color: #2c3e50;">💬 Message:</h3>
                        <p style="white-space: pre-wrap;">' . htmlspecialchars($data['message']) . '</p>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
                        <p>This message was sent from your portfolio contact form.</p>
                        <p>To reply, simply respond to this email or click the email address above.</p>
                    </div>
                </div>
            </body>
            </html>';

            $email = (new Email())
                ->from('mnyambwanoel123@gmail.com')
                ->to('mnyambwanoel123@gmail.com')
                ->replyTo($data['email'])
                ->subject('🔔 New Contact: ' . ($data['subject'] ?? 'No Subject') . ' - from ' . $data['name'])
                ->html($htmlContent)
                ->text(
                    "=== NEW MESSAGE FROM YOUR PORTFOLIO ===\n\n" .
                    "SENDER INFORMATION:\n" .
                    "-------------------\n" .
                    "Name: " . $data['name'] . "\n" .
                    "Email: " . $data['email'] . "\n" .
                    "Subject: " . ($data['subject'] ?? 'No Subject') . "\n\n" .
                    "MESSAGE:\n" .
                    "--------\n" .
                    $data['message'] . "\n\n" .
                    "-------------------\n" .
                    "Reply to this email to respond directly to the sender."
                );

            $this->mailer->send($email);
            
            if ($this->logger) {
                $this->logger->info('✅ Contact form email sent successfully from: ' . $data['email']);
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('❌ Email sending failed: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            }
            throw $e;
        }
    }

    /**
     * Helper method to get PersonalDetails with try-catch
     */
    private function getPersonalDetails()
    {
        try {
            // Try to use the repository
            if (class_exists(PersonalDetails::class)) {
                $repository = $this->entityManager->getRepository(PersonalDetails::class);
                $details = $repository->findOneBy(['isActive' => true], ['updatedAt' => 'DESC']);
                
                if ($details) {
                    return $details;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning('PersonalDetails not available: ' . $e->getMessage());
            }
            
            return null;
        }
    }
}