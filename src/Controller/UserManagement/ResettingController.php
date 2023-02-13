<?php

declare(strict_types=1);

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\User;
use App\Form\UserManagement\ResetPasswordType;
use App\Form\UserManagement\UserEmailType;
use App\Repository\UserManagement\ResettingRepository;
use App\Service\UserManagement\ResettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResettingController extends AbstractController
{
    /**
     * @Route("/password/reset", methods={"GET|POST"}, name="password_reset")
     */
    public function passwordReset(ResettingService $service, Request $request): Response
    {
        $form = $this->createForm(UserEmailType::class, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->sendResetPasswordLink($request);

            return $this->render('UserManagement/Resetting/password_reset_check.html.twig', [
                'email' => $request->get('user_email')['email'],
            ]);
        }

        return $this->render('UserManagement/Resetting/password_reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/password/reset/{token}", methods={"GET|POST"}, name="password_reset_confirm")
     */
    public function passwordResetConfirm(ResettingRepository $repository, Request $request, string $token, TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $repository->findOneBy(['confirmation_token' => $token]);

        if (!$user instanceof UserInterface) {
            // Token not found.
            return new RedirectResponse($this->generateUrl('app_login'));
        } elseif (!$user->isPasswordRequestNonExpired($user::TOKEN_TTL)) {
            // Token has expired.
            $this->addFlash('error', $translator->trans('message.token_expired'));

            return new RedirectResponse($this->generateUrl('password_reset'));
        }

        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->setPassword($user, $user->getPassword());
            $this->addFlash('success', $translator->trans('message.password_has_been_reset'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('UserManagement/Resetting/password_change.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
