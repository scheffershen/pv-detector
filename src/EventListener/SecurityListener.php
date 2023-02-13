<?php

namespace App\EventListener;

use App\Entity\UserManagement\FailedLoginAttempt;
use App\Entity\UserManagement\User;
use App\Entity\UserManagement\Tracking;
use App\Repository\UserManagement\FailedLoginAttemptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Listener responsible to change the redirection when a form is successfully filled.
 */
class SecurityListener implements EventSubscriberInterface
{
    protected $router;
    protected $dispatcher;
    protected $entityManager;
    protected $messageBus;
    protected $authenticationUtils;
    protected $requestStack;
    protected $failedLoginAttemptRepository;
    protected $session;
    protected $securityToken;
    protected $parameter;
    protected $logger;

    public function __construct(UrlGeneratorInterface $router, EventDispatcherInterface $dispatcher, MessageBusInterface $messageBus, AuthenticationUtils $authenticationUtils, RequestStack $requestStack, EntityManagerInterface $entityManager, FailedLoginAttemptRepository $failedLoginAttemptRepository, SessionInterface $session, TokenStorageInterface $securityToken, ParameterBagInterface $parameter, LoggerInterface $logger)
    {
        $this->router = $router;
        $this->dispatcher = $dispatcher;
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->authenticationUtils = $authenticationUtils;
        $this->requestStack = $requestStack;
        $this->failedLoginAttemptRepository = $failedLoginAttemptRepository;
        $this->session = $session;
        $this->securityToken = $securityToken;
        $this->parameter = $parameter;    
        $this->logger = $logger;    
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onLogin',
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onLoginFailure',
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        $request = $event->getRequest();
        $route = $request->get('_route');

        $userDetails = [
            'id' =>             $user->getId(),
            'username' =>       $user->getUsername(),
            'email' =>          $user->getEmail(),
            'authenticated' =>  true
        ];

        $entity = new Tracking();
        $entity->setController('App\Controller\UserManagement\SecurityController');
        $entity->setAction('loginAction');
        $entity->setQueryRequest(json_encode($userDetails));
        $entity->setPathInfo('/'.$request->getLocale().'/login');
        $entity->setHttpMethod($request->getMethod());
        $entity->setIpRequest($request->getClientIp());
        $entity->setLang($request->getLocale());
        $entity->setUriRequest($request->getUri());
        $entity->setCreated((new \DateTime('now')));
        $entity->setUser($user);

        try {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();            
        } catch (\Throwable $exception) {
            throw new \Exception($exception->getMessage());
        }

        if ($route != 'change_password' && null != $route) {
            $duration = 0;
            if ($user->getLastChangePassword()) {
                $now = new \DateTime();
                $interval = $user->getLastChangePassword()->diff($now);
                $duration = $interval->format('%a');
            } 
            
            if ($user->getChangePassword() || $user->getLastChangePassword() == null || $duration >= User::PASSWORD_AGE) {
                $this->dispatcher->addListener(KernelEvents::RESPONSE, [
                            $this,
                            'onKernelResponse'
                ]);
            }

        }
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $response = new RedirectResponse($this->router->generate('change_password'));
        $event->setResponse($response);
    }

    public function onLoginFailure(AuthenticationFailureEvent $event)
    {
        $username = $this->authenticationUtils->getLastUsername();
        $request = $this->requestStack->getCurrentRequest();

        $this->failedLoginAttemptRepository->save(FailedLoginAttempt::createFromRequest($request));

        $existingUser = $this->entityManager->getRepository(User::class)->loadUserByUsername($username);

        if ($existingUser) {
            $userDetails = [
                'id' =>             $existingUser->getId(),
                'username' =>       $existingUser->getUsername(),
                'email' =>          $existingUser->getEmail(),
                'authenticated' =>  false
            ];
        } else {
            $userDetails = [
                'username' =>           $username,
                'authenticated' =>  false
            ];
        }

        $entity = new Tracking();
        $entity->setController('App\Controller\UserManagement\SecurityController');
        $entity->setAction('loginFailureAction');
        $entity->setQueryRequest(json_encode($userDetails));
        $entity->setPathInfo('/'.$request->getLocale().'/login');
        $entity->setHttpMethod($request->getMethod());
        $entity->setIpRequest($request->getClientIp());
        $entity->setLang($request->getLocale());
        $entity->setUriRequest($request->getUri());
        $entity->setCreated((new \DateTime('now')));
        try {
            $this->entityManager->persist($entity);
            $this->entityManager->flush($entity);
        } catch (\Throwable $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }
        
        $maxIdleTime = $this->parameter->get('session_max_idle_time');
        //$this->logger->critical($maxIdleTime);

        if ($maxIdleTime > 0) {
            $this->session->start();
            $lapse = time() - $this->session->getMetadataBag()->getLastUsed();
            //$this->logger->critical($this->session->getMetadataBag()->getLastUsed());
            
            if ($lapse > $maxIdleTime) {
                $this->securityToken->setToken(null);
                //$this->session->getFlashBag()->set('info', 'You have been logged out due to inactivity.');

                $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
            }
        }
    }          
}
