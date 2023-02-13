<?php

declare(strict_types=1);

namespace App\Repository\UserManagement;

use App\Entity\UserManagement\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class ResettingRepository extends UserRepository
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(ManagerRegistry $registry, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($registry);
        $this->passwordEncoder = $passwordEncoder;
    }

    public function setPassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordEncoder->encodePassword($user, $plainPassword));
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);
        $user->setLastChangePassword(new \DateTime('now'));
        $this->save($user);
    }

    public function setToken(User $user, string $token): void
    {
        $user->setConfirmationToken($token);
        $user->setPasswordRequestedAt(new \DateTime());
        $this->save($user);
    }

    private function save(User $user): void
    {
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();
    }
}
