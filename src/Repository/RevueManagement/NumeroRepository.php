<?php

namespace App\Repository\RevueManagement;

use App\Entity\RevueManagement\Numero;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Numero|null find($id, $lockMode = null, $lockVersion = null)
 * @method Numero|null findOneBy(array $criteria, array $orderBy = null)
 * @method Numero[]    findAll()
 * @method Numero[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NumeroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Numero::class);
    } 

    public function numerosSansOrAvecRapport(?PersistentCollection $clients = null) 
    {
        if ($clients) {
            $queryBuilder = $this->createQueryBuilder('n')
                    ->join('n.revue', 'r')
                    ->join('r.clients', 'c')
                    ->where('n.isValid = true')
                    ->orWhere('n.state = :published')
                    ->orWhere('n.state = :controlled')
                    ->andWhere('r.isValid = true')
                    ->andWhere('c.id IN (:clients)')
                    ->setParameters(['clients' => $clients, 'published' => Numero::PUBLISHED, 'controlled' => Numero::CONTROLLED])
                    ->orderBy('n.updateDate', 'DESC')
                    ->setMaxResults(10);
        } else {
            $queryBuilder = $this->createQueryBuilder('n')
                    ->join('n.revue', 'r')
                    ->where('n.isValid = true')
                    ->andWhere('n.state = :state')
                    ->andWhere('r.isValid = true')
                    ->setParameters(['state' => Numero::TREATMENT])
                    ->orderBy('n.updateDate', 'DESC')
                    ->setMaxResults(10); 
        }

        return $queryBuilder->getQuery()->getResult();

    }     
}
