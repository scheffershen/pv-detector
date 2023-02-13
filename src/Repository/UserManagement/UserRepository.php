<?php

namespace App\Repository\UserManagement;

use App\Entity\UserManagement\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[] findAll()
 * @method User[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByUsername(string $usernameOrEmail)
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
                'SELECT u
                FROM App\Entity\UserManagement\User u
                WHERE u.username = :query
                OR u.email = :query'
            )
            ->setParameter('query', $usernameOrEmail)
            ->getOneOrNullResult();
    }
    
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $user->setLastChangePassword(new \DateTime('now'));
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findAllByRole(string $role)
    {
        $queryBuilder = $this->createQueryBuilder('u')
             ->where('u.roles LIKE :role') //roles =json
             ->andWhere('u.isDeleted = false')
             ->setParameter('role', '%"'.$role.'"%')
             ;

        $limit = 1000;
        $offset = 0;

        while (true) {
            $queryBuilder->setFirstResult($offset);
            $queryBuilder->setMaxResults($limit);

            $users = $queryBuilder->getQuery()->getResult();

            if (0 === \count($users)) {
                break;
            }

            foreach ($users as $user) {
                yield $user;
                $this->_em->detach($user);
            }

            $offset += $limit;
        }

        //return $qb->getQuery()->getResult();
    }

    public function findAllByRoleGestionnaireLecteur()
    {
        $queryBuilder = $this->createQueryBuilder('u')
             ->where('u.roles LIKE :gestionnaire') //roles =json
             ->orWhere('u.roles LIKE :lecteur')
             ->andWhere('u.isEnable = true')
             ->setParameters(['gestionnaire' => '%"GESTIONNAIRE"%',
                              'lecteur' => '%"LECTEUR"%'])
             ;

        $limit = 1000;
        $offset = 0;

        while (true) {
            $queryBuilder->setFirstResult($offset);
            $queryBuilder->setMaxResults($limit);

            $users = $queryBuilder->getQuery()->getResult();

            if (0 === \count($users)) {
                break;
            }

            foreach ($users as $user) {
                yield $user;
                $this->_em->detach($user);
            }

            $offset += $limit;
        }

        //return $qb->getQuery()->getResult();
    }
    public function countAll(string $role): int
    {
        $count = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.roles LIKE :role') //roles =json
            ->andWhere('u.isDeleted = false')
            ->setParameter('role', '%"'.$role.'"%')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }

    public function findCount(string $role): int
    {
        $cache = new FilesystemAdapter();

        $count = $cache->get('users_count_'.$role, function () use ($role) {
            return $this->countAll($role);
        });

        return (int) $count;
    }

    public function patientSearch(array $data)
    {
        $qb = $this->createQueryBuilder('u')
             ->where('u.roles LIKE :role')->setParameter('role', '%"ROLE_PATIENT"%')
             ->andWhere('u.isDeleted = false');
        if (!empty($data['firstname'])) {
            $qb->andWhere('u.firstname LIKE :firstname')->setParameter('firstname', "%{$data['firstname']}%");
        }
        if (!empty($data['lastname'])) {
            $qb->andWhere('u.lastname LIKE :lastname')->setParameter('lastname', "%{$data['lastname']}%");
        }
        if (!empty($data['dateNaissance'])) {
            $qb->andWhere('u.dateNaissance = :dateNaissance')->setParameter('dateNaissance', (new \DateTime($data['dateNaissance']) ));
        }
        if (!empty($data['email'])) {
            $qb->andWhere('u.email = :email')->setParameter('email', "%{$data['email']}%");
        }

        return $qb->getQuery()->getResult();
        // $limit = 1000;
        // $offset = 0;

        // while (true) {
        //     $qb->setFirstResult($offset);
        //     $qb->setMaxResults($limit);

        //     $users = $qb->getQuery()->getResult();

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

    public function ajax(array $get)
    {
        // $draw = $request->request->get('draw'); //$_POST['draw'];
        // $row = $request->request->get('start'); //$_POST['start'];
        // $rowperpage = $request->request->get('length'); //$_POST['length']; // Rows display per page
        // $columnIndex = $request->request->get('order')[0]['column']; //$_POST['order'][0]['column']; // Column index
        // $columnName = $request->request->get('columns')[$columnIndex]['data']; //$_POST['columns'][$columnIndex]['data']; // Column name
        // $columnSortOrder = $request->request->get('order')[0]['dir']; //$_POST['order'][0]['dir']; // asc or desc
        // $searchValue = $request->request->get('search')['value']; //$_POST['search']['value']; // Search value

        // $get['draw']
        $qb = $this->createQueryBuilder('u')
             ->andWhere('u.isDeleted = false')
             ;

        return $qb->getQuery()->getResult();
    }

    public function findByRespondableClient(array $clients) 
    {
        $queryBuilder = $this->createQueryBuilder('u')
                    ->join('u.clientsResponsable', 'clientsResponsable')
                    ->join('u.clientsBackupResponsable', 'clientsBackupResponsable')
                    ->where('u.isEnable = true')
                    ->orWhere('clientsResponsable.id IN (:clients)')
                    ->orWhere('clientsBackupResponsable.id IN (:clients)')
                    ->setParameter('clients', $clients)
                    ->orderBy('u.username', 'ASC')
                ;   

        return $queryBuilder->getQuery()->getResult();
    }    
}
