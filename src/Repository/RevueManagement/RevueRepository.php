<?php

namespace App\Repository\RevueManagement;

use App\Entity\RevueManagement\Revue;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Revue|null find($id, $lockMode = null, $lockVersion = null)
 * @method Revue|null findOneBy(array $criteria, array $orderBy = null)
 * @method Revue[]    findAll()
 * @method Revue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RevueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Revue::class);
    }

    public function findByClient(int $id)
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.clients', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->orderBy('r.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByAbonnementEnd30Days()
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.endDate <= :end30')
            ->andWhere('r.isValid = 1')
            ->addOrderBy('r.endDate', 'ASC')
            ->addOrderBy('r.title', 'ASC')
            ->setParameters(
                [
                    'end30' => new \DateTime('+30 days')
                ]
            );

        return $qb->getQuery()->getResult();
    }      
}
