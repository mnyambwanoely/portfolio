<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security as CoreSecurity;

/**
 * @Route("/admin")
 */
class AdminSecurityController extends AbstractController
{
    /**
     * Admin Login Page
     * 
     * @Route("/login", name="admin_login", methods={"GET", "POST"})
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect to dashboard
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'page_title' => 'Admin Login',
        ]);
    }

    /**
     * Admin Logout
     * 
     * @Route("/logout", name="admin_logout", methods={"GET"})
     */
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    /**
     * Access Denied Page
     * 
     * @Route("/access-denied", name="admin_access_denied", methods={"GET"})
     */
    public function accessDenied(): Response
    {
        return $this->render('admin/security/access_denied.html.twig', [
            'page_title' => 'Access Denied',
        ]);
    }

    /**
     * Check if user is logged in (JSON endpoint)
     * 
     * @Route("/check-auth", name="admin_check_auth", methods={"GET"})
     */
    public function checkAuth(): Response
    {
        $user = $this->getUser();
        
        return $this->json([
            'authenticated' => $user !== null,
            'username' => $user ? $user->getUserIdentifier() : null,
            'roles' => $user ? $user->getRoles() : [],
        ]);
    }
}