<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\UserManagement\Plateforme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 */
final class PlateformeFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->loadPlateforme($manager);
    }

    public function loadPlateforme(ObjectManager $manager): void
    {        
        foreach ($this->getData() as [$nom, $email, $logo, $version, $poweredBy]) {
            $entity = new Plateforme();
            $entity->setNom($nom);
            $entity->setEmail($email);
            $entity->setLogoUri($logo);
            $entity->setVersion($version);
            $entity->setPoweredBy($poweredBy);
            $entity->setCreateUser($this->getReference(UserFixtures::ADMIN));
            $entity->setUpdateUser($this->getReference(UserFixtures::ADMIN));
            $manager->persist($entity);
        }
        $manager->flush();
    }

    private function getData(): array
    {
        return [
            ['Plateforme PV detector', 'test-veille@xxx.com', 'veille.jpg', 'V1.0.0', 'Powered by xxx.com'],
        ];
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['DEV', 'PROD', 'TEST'];
    }
}
