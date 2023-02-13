<?php

namespace App\Repository\UserManagement;

use App\Entity\LovManagement\Jour;
use App\Entity\UserManagement\Client;
use App\Entity\UserManagement\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findByDci(int $id)
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.dcis', 'd')
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ->orderBy('d.title', 'ASC')
            ->getQuery()
            ->getResult();
    }  

    public function findByRevue(int $id)
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.revues', 'r')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->orderBy('r.title', 'ASC')
            ->getQuery()
            ->getResult();
    }  

    public function getLastClients()
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.isDeleted = false')
            ->orderBy('c.updateDate', 'DESC')
            ->setMaxResults(5)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByClientsDuJour() 
    {
        $today = (new \DateTime())->format("Y-m-d");
        $dayofweek = date('w', strtotime($today));
        switch ($dayofweek) {
            case 1:
                $queryBuilder = $this->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.jourBilanHebdomadaire', 'jour')
                    ->where('c.isValid = true')
                    ->andWhere('jour.code = :code')
                    ->setParameter('code', Jour::LUNDI)
                    ->orderBy('c.name', 'ASC')
                ;
                break;
            case 2:
                $queryBuilder = $this->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.jourBilanHebdomadaire', 'jour')
                    ->where('c.isValid = true')
                    ->andWhere('jour.code = :code')
                    ->setParameter('code', Jour::MARDI)
                    ->orderBy('c.name', 'ASC')
                ;
                break;
            case 3:
                $queryBuilder = $this->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.jourBilanHebdomadaire', 'jour')
                    ->where('c.isValid = true')
                    ->andWhere('jour.code = :code')
                    ->setParameter('code', Jour::MERCREDI)
                    ->orderBy('c.name', 'ASC')
                ;
                break;    
            case 4:
                $queryBuilder = $this->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.jourBilanHebdomadaire', 'jour')
                    ->where('c.isValid = true')
                    ->andWhere('jour.code = :code')
                    ->setParameter('code', Jour::JEUDI)
                    ->orderBy('c.name', 'ASC')
                ;
                break;                  
            case 5:
                $queryBuilder = $this->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.jourBilanHebdomadaire', 'jour')
                    ->where('c.isValid = true')
                    ->andWhere('jour.code = :code')
                    ->setParameter('code', Jour::VENDREDI)
                    ->orderBy('c.name', 'ASC')
                ;                
                break;                             
            default:
                $queryBuilder = $this->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.jourBilanHebdomadaire', 'jour')
                    ->where('c.isValid = true')
                    ->andWhere('jour.code = :code')
                    ->setParameter('code', Jour::SAMEDI)
                    ->orderBy('c.name', 'ASC')
                ;             
                break; 
        }
        return $queryBuilder->getQuery()->getResult();
    }

    public function mesRapportClients(User $user) 
    {
        // option 1
        //$clients = new ArrayCollection(
        //    array_merge($user->getClientsResponsable(), $user->getClientsBackupResponsable())
        //);

        // option 2
        if (\count($user->getClientsResponsable()) > 0 && \count($user->getClientsBackupResponsable()) > 0) {
            $queryBuilder = $this->createQueryBuilder('c')
                        ->join('c.jourBilanHebdomadaire', 'j')
                        ->where('c.isValid = true')
                        ->orWhere('c.id IN (:clientsResponsable)')
                        ->orWhere('c.id IN (:clientsBackupResponsable)')
                        ->setParameters(['clientsResponsable' =>$user->getClientsResponsable(), 'clientsBackupResponsable' => $user->getClientsBackupResponsable()])
                        ->orderBy('j.sort', 'ASC')
                    ; 
            return $queryBuilder->getQuery()->getResult();  

        } elseif (\count($user->getClientsResponsable()) > 0) {
            $queryBuilder = $this->createQueryBuilder('c')
                        ->join('c.jourBilanHebdomadaire', 'j')
                        ->where('c.isValid = true')
                        ->andWhere('c.id IN (:clientsResponsable)')
                        ->setParameters(['clientsResponsable' =>$user->getClientsResponsable()])
                        ->orderBy('j.sort', 'ASC')
                    ; 
            return $queryBuilder->getQuery()->getResult(); 

        } elseif (\count($user->getClientsBackupResponsable()) > 0) {
            $queryBuilder = $this->createQueryBuilder('c')
                        ->join('c.jourBilanHebdomadaire', 'j')
                        ->where('c.isValid = true')
                        ->andWhere('c.id IN (:clientsBackupResponsable)')
                        ->setParameters(['clientsBackupResponsable' =>$user->getClientsBackupResponsable()])
                        ->orderBy('j.sort', 'ASC')
                    ; 
            return $queryBuilder->getQuery()->getResult();              
        }
        
        return null;
    }
}
