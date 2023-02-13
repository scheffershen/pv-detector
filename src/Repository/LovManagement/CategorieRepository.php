<?php

namespace App\Repository\LovManagement;

use App\Entity\LovManagement\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categorie::class);
    }

    public function findByLastCategorie()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isValid = true')
            ->orderBy('c.sort', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }    
}
