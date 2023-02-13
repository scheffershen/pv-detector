<?php

namespace App\Repository\RevueManagement;

use App\Entity\RevueManagement\Block;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Block|null find($id, $lockMode = null, $lockVersion = null)
 * @method Block|null findOneBy(array $criteria, array $orderBy = null)
 * @method Block[]    findAll()
 * @method Block[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Block::class);
    }

    public function findDciByText(Block $block, string $dci): bool
    {
        $result = $this->createQueryBuilder('b')
            ->select('b.text')
            ->where('b.id = :id')
            ->andWhere('MATCH (b.text) AGAINST (:text) > 1')
            ->setParameters(['text' => $dci, 'id' => $block->getId()])
            ->getQuery()
            ->getResult();

        if ($result) {
            return true;
        }

        return false;
    }

    public function findAllByDci(string $dci)
    {
        $queryBuilder = $this->createQueryBuilder('b')
                 //->select('b.text, p.id as page_id, i.id as image_id, n.id as numero_id, n.title as numero_title')
                 ->select('b.text, p.id as page_id, i.id as image_id, n.id as numero_id, n.title as numero')
                 ->join('b.page', 'p')
                 ->join('p.image', 'i')
                 ->join('i.numero', 'n')
                 ->where('MATCH (b.text) AGAINST (:text) > 1')
                 // ->andWhere('p.isDeleted = :isDeleted')
                 // ->andWhere('i.isDeleted = :isDeleted')
                 ->andWhere('n.isValid = :isValid')
                 //->setParameters(['text'=>$dci,'isDeleted'=>false,'isValid'=>true])
                 //->orderBy('numero.id', 'DESC');
                 //->setParameters(['text'=>$dci,'isDeleted'=>false,'isValid'=>true])
                 ->setParameters(['text' => $dci, 'isValid' => true])
                 //->orderBy('n.id', 'DESC')
                 ->getQuery()
                 ->getResult()
                 ;

        return $queryBuilder;
        // $limit = 1000;
        // $offset = 0;

        // while (true) {
        //     $queryBuilder->setFirstResult($offset);
        //     $queryBuilder->setMaxResults($limit);

        //     $users = $queryBuilder->getQuery()->getResult();

        //     if (count($users) === 0) {
        //         break;
        //     }

        //     foreach ($users as $user) {
        //         yield $user;
        //         $this->_em->detach($user);
        //     }

        //     $offset += $limit;
        // }
    }

    // /**
    //  * @return Block[] Returns an array of Block objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Paragraph
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
