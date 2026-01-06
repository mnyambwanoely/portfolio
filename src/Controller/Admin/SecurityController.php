<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends AbstractController
{
    // HAKUNA CONSTRUCTOR! KAMA UNA CONSTRUCTOR, FUTA AU COMMENT
    
    /**
     * @Route("/admin/login", name="admin_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Check if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        // Get error if any
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'page_title' => 'Admin Login',
        ]);
    }

    /**
     * @Route("/admin/logout", name="admin_logout", methods={"GET","POST"})
     */
    public function logout(Request $request): Response
    {
        // Fallback: if the firewall didn't handle logout, ensure the session gets invalidated
        try {
            $session = $request->getSession();
            if ($session && $session->isStarted()) {
                $session->invalidate();
            }
        } catch (\Throwable $e) {
            // ignore session errors - best effort invalidation
        }

        $response = $this->redirectToRoute('admin_login');
        // Clear the session cookie as an extra precaution
        $response->headers->clearCookie(session_name());

        return $response;
    }
}