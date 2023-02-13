<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\UserManagement\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @codeCoverageIgnore
 * php bin/console doctrine:fixtures:load --env=dev --group DEV
 */
final class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public const ADMIN = 'admin';
    public const GESTIONNAIRE = 'gestionnaire';
    public const LECTEUR = 'lecteur';

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $this->loadUser($manager);
    }

    public function loadUser(ObjectManager $manager)
    {
        foreach ($this->getData() as [$firstname, $lastname, $username, $password, $email, $roles]) {
            $user = new User();
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setUsername($username);
            $user->setPassword($this->passwordEncoder->encodePassword(
                $user,
                $password
            ));
            $user->setLastChangePassword(new \DateTime('now'));
            $user->setEmail($email);
            $user->setRoles($roles);
            $manager->persist($user);
            $this->addReference($username, $user);
        }
        $manager->flush();
    }

    private function getData(): array
    {
        return [
            ['admin', 'admin', 'admin', 'admin2021!', 'admin@xxx.com', ['ROLE_ADMIN']],
            ['gestionnaire', 'gestionnaire', 'gestionnaire', 'gestionnaire2021!', 'gestionnaire@xxx.com', ['ROLE_GESTIONNAIRE']],
            ['lecteur', 'lecteur', 'lecteur', 'lecteur2021!', 'lecteur@xxx.com', ['ROLE_LECTEUR']],
            ['client', 'client', 'client', 'client2021!', 'client@xxx.com', ['ROLE_CLIENT']],
        ];
    }

    public static function getGroups(): array
    {
        return ['DEV', 'PROD', 'TEST'];
    }
}
