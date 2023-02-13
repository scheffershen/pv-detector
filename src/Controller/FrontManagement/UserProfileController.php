<?php

namespace App\Controller\FrontManagement;

use App\Entity\UserManagement\User;
use App\Form\UserManagement\UserType;
use App\Service\UserManagement\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserProfileController extends AbstractController
{
    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/user/profile/{id<\d+>}",methods={"GET", "POST"}, name="user_profile")
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function profile(Request $request, User $user, UserService $service, TranslatorInterface $translator): Response
    {
        if ($user !== $this->getUser()) {
            throw $this->createAccessDeniedException($translator->trans('message.denied'));
        }

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->update($user);
        }

        return $this->render('FrontManagement/User/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
