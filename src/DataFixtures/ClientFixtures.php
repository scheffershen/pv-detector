<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\UserManagement\Client;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 * php bin/console doctrine:fixtures:load --env=dev --group DEV --purger
 */
final class ClientFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->loadClient($manager);
    }

    public function loadClient(ObjectManager $manager): void
    {
        foreach ($this->getData() as [$name]) {
            $entity = new Client();
            $entity->setName($name);
            $entity->setJourBilanHebdomadaire($this->getReference(JourFixtures::JOUR));
            $entity->setRespondableClient($this->getReference(UserFixtures::GESTIONNAIRE));
            $entity->setCreateUser($this->getReference(UserFixtures::ADMIN));
            $entity->setUpdateUser($this->getReference(UserFixtures::ADMIN));
            $manager->persist($entity);
        }
        $manager->flush();
    }

    private function getData(): array
    {
        return [
            ['Client No.1'],
            ['Client No.2']
        ];
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            JourFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['DEV', 'TEST'];
    }
}
