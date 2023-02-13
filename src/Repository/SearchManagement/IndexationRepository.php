<?php

namespace App\Repository\SearchManagement;

use App\Entity\RevueManagement\Numero;
use App\Entity\SearchManagement\Dci;
use App\Entity\SearchManagement\Indexation;
use App\Entity\UserManagement\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Indexation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Indexation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Indexation[]    findAll()
 * @method Indexation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IndexationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Indexation::class);
    }

    /**
     * version 1 
    */
    public function findByNumero(Numero $numero)
    {
        return $this->createQueryBuilder('i')
            ->select('n.id as id, n.title as numero, d.title as dci, d.id as dci_id, count(d.id) as count_dci')
            ->join('i.dci', 'd')
            ->join('i.numero', 'n')
            ->andWhere('n.id = :id')
            ->setParameter('id', $numero->getId())
            ->groupBy('d.id')->orderBy('count_dci', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * version 2, URS|M7
     * numero, image, page, dci
    */
    public function findByNumero2(Numero $numero)
    {
        return $this->createQueryBuilder('i')
            ->join('i.numero', 'n')
            ->join('i.image', 'img')
            ->join('i.dci', 'd')
            ->where('n.id = :id')
            //->andWhere('i.occurrence >= 1')
            ->setParameter('id', $numero->getId())
            ->addOrderBy('img.numeroPage', 'ASC')
            ->addOrderBy('d.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByNumeroDci(Numero $numero, Dci $dci)
    {
        return $this->createQueryBuilder('i')
            ->select('i')
            ->join('i.page', 'p')
            ->where('i.numero = :numero')
            ->andWhere('i.dci = :dci')
            ->setParameters(['numero' => $numero, 'dci' => $dci])
            ->orderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByDci(Dci $dci)
    {
        return $this->createQueryBuilder('i')
            ->select('n.id as id, d.title as dci, d.id as dci_id, n.title as numero, n.receiptDate as receiptDate, r.title as revue, count(n.id) as count_numero')
            ->join('i.dci', 'd')
            ->join('i.numero', 'n')
            ->join('n.revue', 'r')
            ->andWhere('d.id = :id')
            ->setParameter('id', $dci->getId())
            ->groupBy('n.id')->orderBy('count_numero', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByClient(Client $client)
    {
        return $this->createQueryBuilder('i')
            ->select('i.id as id, d.title as dci, n.id as numero_id, n.title as numero, n.receiptDate as receiptDate, r.title as revue')
            ->join('i.dci', 'd')
            ->join('i.numero', 'n')
            ->join('n.revue', 'r')
            ->join('r.clients', 'c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $client->getId())
            ->groupBy('i.id')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalByDci(int $dci_id)
    {
        return $this->createQueryBuilder('i')
            ->select('count(i.id)')
            ->join('i.dci', 'd')
            ->andWhere('d.id = :id')
            ->setParameter('id', $dci_id)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalByNumero(int $numero_id)
    {
        return $this->createQueryBuilder('i')
            ->select('count(i.id)')
            ->join('i.numero', 'n')
            ->andWhere('n.id = :id')
            ->setParameter('id', $numero_id)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalByClient(int $client_id)
    {
        return $this->createQueryBuilder('i')
            ->select('count(i.id)')
            ->join('i.numero', 'n')
            ->join('n.revue', 'r')
            ->join('r.clients', 'c')
            ->andWhere('n.id = :id')
            ->setParameter('id', $client_id)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAll()
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->select('i');

        $limit = 1000;
        $offset = 0;

        while (true) {
            $queryBuilder->setFirstResult($offset);
            $queryBuilder->setMaxResults($limit);

            $indexs = $queryBuilder->getQuery()->getResult();

            if (0 === \count($indexs)) {
                break;
            }

            foreach ($indexs as $index) {
                yield $index;
                $this->_em->detach($index);
            }

            $offset += $limit;
        }
    }
}
