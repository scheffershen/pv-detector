<?php

namespace App\Repository\RevueManagement;

use App\Entity\RevueManagement\Numero;
use App\Entity\RevueManagement\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Page|null find($id, $lockMode = null, $lockVersion = null)
 * @method Page|null findOneBy(array $criteria, array $orderBy = null)
 * @method Page[]    findAll()
 * @method Page[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * getPagesFromRevue.
     *
     * @return Page[]
     */
    public function getPagesFromRevue(Numero $numero)
    {
        return $this->createQueryBuilder('p')
                    ->where('p.numero = :numero')
                    ->setParameter('numero', $numero)
                    ->getQuery()
                    ->getResult();
    }

    public function findDciBy(Page $page, string $dci): bool
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.content')
            ->where('p.id = :id')
            ->andWhere('MATCH (p.content) AGAINST (:content) > 1')
            ->setParameters(['content' => $dci, 'id' => $page->getId()])
            ->getQuery()
            ->getResult();

        if ($result) {
            return true;
        }

        return false;
    }

    public function findDciByBlocksText(Page $page, string $dci): bool
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.blocksText')
            ->where('p.id = :id')
            ->andWhere('MATCH (p.blocksText) AGAINST (:dci) > 1')
            ->setParameters(['dci' => $dci, 'id' => $page->getId()])
            ->getQuery()
            ->getResult();

        if ($result) {
            return true;
        }

        return false;
    }

    public function findDciByBlocksText2(string $dci): bool
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.image', 'i')
            ->join('i.numero', 'n')
            ->select('p.blocksText')
            ->where('p.id = :id')
            ->andWhere('MATCH (p.blocksText) AGAINST (:dci) > 1')
            ->setParameters(['dci' => $dci])
            ->getQuery()
            ->getResult();

        if ($result) {
            return true;
        }

        return false;
    }
}
