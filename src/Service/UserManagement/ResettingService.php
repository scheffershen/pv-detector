<?php

declare(strict_types=1);

namespace App\Service\UserManagement;

use App\Entity\UserManagement\User;
use App\Message\UserManagement\SendResetPasswordLink;
use App\Repository\UserManagement\ResettingRepository;
use App\Service\AbstractService;
use App\Utils\TokenGenerator;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResettingService extends AbstractService
{
    /**
     * @var ResettingRepository
     */
    private $repository;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var TokenGenerator
     */
    private $generator;
    /**
     * @var TranslatorInterface
     */    
    private $translator;

    public function __construct(
        ContainerInterface $container,
        ResettingRepository $repository,
        MessageBusInterface $messageBus,
        TokenGenerator $generator,
        TranslatorInterface $translator
    ) {
        parent::__construct($container);
        $this->repository = $repository;
        $this->messageBus = $messageBus;
        $this->generator = $generator;
        $this->translator = $translator;
    }

    public function sendResetPasswordLink(Request $request): void
    {
        /** @var User $user */
        $user = $this->repository->findOneBy(['email' => $request->get('user_email')['email']]);

        if (!$user->isPasswordRequestNonExpired($user::RETRY_TTL)) {
            $this->updateToken($user);
            $this->messageBus->dispatch(new SendResetPasswordLink($user));
            $this->addFlash('success', 'message.emailed_reset_link');
        } else {
            $this->addFlash('success', $this->translator->trans('message.password_request_non_expired'));
        }
    }

    /**
     * Generating a Confirmation Token.
     */
    private function generateToken(): string
    {
        return $this->generator->generateToken();
    }

    /**
     * Refreshing a Confirmation Token.
     */
    private function updateToken(User $user): void
    {
        $this->repository->setToken($user, $this->generateToken());
    }
}
