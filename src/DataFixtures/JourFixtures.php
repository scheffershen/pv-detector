<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\LovManagement\Jour;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 */
final class JourFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const JOUR = 'lun';

    public function load(ObjectManager $manager): void
    {
        $this->loadJour($manager);
    }

    public function loadJour(ObjectManager $manager): void
    {
        $sort = 1;
        foreach ($this->getData() as [$name, $code, $reference]) {
            $entity = new Jour();
            $entity->setTitle($name);
            $entity->setCode($code);
            $entity->setSort($sort);
            $entity->setCreateUser($this->getReference(UserFixtures::ADMIN));
            $entity->setUpdateUser($this->getReference(UserFixtures::ADMIN));
            $manager->persist($entity);
            $this->addReference($reference, $entity);
            ++$sort;
        }
        $manager->flush();
    }

    private function getData(): array
    {
        return [
            ['Lundi', 'lun', 'lun'],
            ['Mardi', 'mar', 'mar'],
            ['Mercredi', 'mer', 'mer'],
            ['Jeudi', 'jeu', 'jeu'],
            ['Vendredi', 'ven', 'ven'],
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
