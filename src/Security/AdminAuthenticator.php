<?php

namespace App\Security;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AdminAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    private $entityManager;
    private $router;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * Check if this authenticator supports the request
     */
    public function supports(Request $request): ?bool
    {
        // Only support login requests to admin_login
        return $request->attributes->get('_route') === 'admin_login' 
            && $request->isMethod('POST');
    }

    /**
     * Authenticate the user
     */
    public function authenticate(Request $request): Passport
    {
        // Get credentials from request and validate
        $email = (string) $request->request->get('_username', '');
        $password = (string) $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token');

        // Add debug log of request payload and headers
        $this->logger->error('AdminAuthenticator: request payload', [
            'method' => $request->getMethod(),
            'content_type' => $request->headers->get('content-type'),
            'content' => $request->getContent(),
            'post_vars' => $request->request->all(),
            'files' => $request->files->all(),
            'headers' => $request->headers->all(),
        ]);
        $this->logger->debug('AdminAuthenticator: attempting authenticate', [
            'email' => $email,
            'request_keys' => array_keys($request->request->all()),
        ]);

        // Basic validation
        if ('' === $email) {
            throw new CustomUserMessageAuthenticationException('Email is required.');
        }
        if ('' === $password) {
            throw new CustomUserMessageAuthenticationException('Password is required.');
        }

        // Store last username in session
        if ($request->hasSession()) {
            $request->getSession()->set('_security.last_username', $email);
        }

        // Create passport with user and credentials
        return new Passport(
            new UserBadge($email, function(string $userIdentifier) {
                // Find user in database
                $this->logger->debug('AdminAuthenticator: looking up user', ['userIdentifier' => $userIdentifier]);
                $user = $this->entityManager->getRepository(Admin::class)
                    ->findOneBy(['email' => $userIdentifier]);

                if (!$user) {
                    $this->logger->debug('AdminAuthenticator: user not found', ['userIdentifier' => $userIdentifier]);
                    throw new UserNotFoundException();
                }

                $this->logger->debug('AdminAuthenticator: user found', ['id' => $user->getId(), 'email' => $user->getEmail()]);

                return $user;
            }),
            new PasswordCredentials($password),
            [
                // Add CSRF token validation
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    /**
     * Handle successful authentication
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect to admin dashboard on success
        return new RedirectResponse($this->router->generate('admin_dashboard'));
    }

    /**
     * Handle authentication failure
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Store error in session (using string instead of class constant)
        if ($request->hasSession()) {
            $request->getSession()->set('_security.last_error', $exception);
        }
        
        // Redirect back to login page
        return new RedirectResponse($this->router->generate('admin_login'));
    }

    /**
     * Start authentication process (when user tries to access protected page)
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Redirect to login page
        return new RedirectResponse($this->router->generate('admin_login'));
    }
}