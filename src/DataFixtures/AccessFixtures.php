<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\LovManagement\Access;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 */
final class AccessFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->loadAccess($manager);
    }

    public function loadAccess(ObjectManager $manager): void
    {
        $sort = 1;
        foreach ($this->getData() as [$name, $code, $reference]) {
            $entity = new Access();
            $entity->setTitle($name);
            $entity->setCode($code);
            $entity->setSort($sort);
            $entity->setCreateUser($this->getReference(UserFixtures::ADMIN));
            $entity->setUpdateUser($this->getReference(UserFixtures::ADMIN));
            $manager->persist($entity);
            ++$sort;
        }
        $manager->flush();
    }

    private function getData(): array
    {
        return [
            ['Papier', 'papier', 'papier'],
            ['Web', 'web', 'web'],
            ['Web + Papier', 'web+papier', 'web+papier'],
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
