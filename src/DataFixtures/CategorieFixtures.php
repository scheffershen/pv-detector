<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\LovManagement\Categorie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 */
final class CategorieFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->loadCategorie($manager);
    }

    public function loadCategorie(ObjectManager $manager): void
    {
        $sort = 1;
        foreach ($this->getData() as [$name, $code, $reference]) {
            $entity = new Categorie();
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
            ['Produit', 'produit', 'produit'],
            ['Principe Actif', 'principe', 'principe'],
            ['Classe thérapeutique', 'classe', 'classe'],
            ['Domaine thérapeutique', 'domaine', 'domaine'],
            ['Forme', 'forme', 'forme'],
            ['Autre', 'auture', 'auture'],
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
