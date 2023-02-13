<?php

namespace App\Repository\SearchManagement;

use App\Entity\SearchManagement\Dci;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Dci|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dci|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dci[]    findAll()
 * @method Dci[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DciRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dci::class);
    }

    public function getDcisByClients($clients)
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.clients', 'c')
            ->where('c.id IN (:clients)')
            ->andWhere('d.isValid = :isValid')
            ->setParameters(['clients' => $clients, 'isValid' => true])
            ->orderBy('d.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByClient(int $id)
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.clients', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->orderBy('d.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll()
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->select('d')
            ->where('d.isDeleted = false')
            ->orderBy('d.title', 'DESC');

        $limit = 1000;
        $offset = 0;

        while (true) {
            $queryBuilder->setFirstResult($offset);
            $queryBuilder->setMaxResults($limit);

            $dcis = $queryBuilder->getQuery()->getResult();

            if (0 === \count($dcis)) {
                break;
            }

            foreach ($dcis as $dci) {
                yield $dci;
                $this->_em->detach($dci);
            }

            $offset += $limit;
        }
    }
}
