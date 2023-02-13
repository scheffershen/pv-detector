<?php

namespace App\Security\RevueManagement;

use App\Entity\RevueManagement\Numero;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class NumeroEditVoter extends Voter
{
    private Security $security;
    private SessionInterface $session;
    private TranslatorInterface $translator;
    private EventDispatcherInterface $dispatcher;
    private $url = null;
    private UrlGeneratorInterface $router;

    public function __construct(Security $security, SessionInterface $session, TranslatorInterface $translator, UrlGeneratorInterface $router, EventDispatcherInterface $dispatcher)
    {
        $this->security = $security;
        $this->session = $session;
        $this->translator = $translator;
        $this->dispatcher = $dispatcher;
        $this->router = $router;
    }

    protected function supports($attribute, $subject)
    {
        return \in_array($attribute, ['NUMERO_EDIT'], true)
            && $subject instanceof Numero;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        switch ($attribute) {
            case 'NUMERO_EDIT':
                return $this->canEdit($subject, $token);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit(Numero $numero, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        } elseif ($this->security->isGranted('ROLE_GESTION_NUMEROS')) {
            if ($numero->getState() == Numero::PUBLISHED || $numero->getState() == Numero::CONTROLLED ) {
                $this->session->getFlashBag()->add('error', $this->translator->trans('numero.have_published'));
                $this->url = $this->router->generate('admin_numero_show', ['id'=> $numero->getId()]);                
                $this->dispatcher->addListener(KernelEvents::RESPONSE, [$this, 'onKernelResponse']);                 
                return false;
            }            
            return true;
        }

        return false;
    }
   
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = new RedirectResponse($this->url);
        $event->setResponse($response);
    }     
}