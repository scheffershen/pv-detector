<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\LovManagement\TypeLien;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 */
final class TypeLienFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->loadTypeLien($manager);
    }

    public function loadTypeLien(ObjectManager $manager): void
    {
        $sort = 1;
        foreach ($this->getData() as [$name, $code, $reference]) {
            $entity = new TypeLien();
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
            ['GED-Formulaire', 'formulaire', 'formulaire'],
            ['GED-Procédure', 'procedure', 'procedure'],
            ['GED-Mode Opératoire', 'mode', 'mode'],
            ['GED-Document de référence', 'document', 'document'],
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
