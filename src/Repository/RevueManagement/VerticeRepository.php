<?php

namespace App\Repository\RevueManagement;

use App\Entity\RevueManagement\Vertice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Vertice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vertice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vertice[]    findAll()
 * @method Vertice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerticeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vertice::class);
    }

    // /**
    //  * @return Vertice[] Returns an array of Vertice objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Vertice
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
