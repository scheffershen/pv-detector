<?php

namespace App\Repository\UserManagement;

use App\Entity\UserManagement\FailedLoginAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FailedLoginAttemptRepository extends ServiceEntityRepository
{
    private const MAX_ATTEMPTS = 5;
    private const LAST_MINUTE = 'PT1M';
    private const LAST_TEN_MINUTES = 'PT10M';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FailedLoginAttempt::class);
    }

    public function save(FailedLoginAttempt $failedLoginAttempt): void
    {
        $this->_em->persist($failedLoginAttempt);
        $this->_em->flush();
    }

    public function canLogin(string $signature): bool
    {
        if (0 === $this->countAttempts($signature, self::LAST_MINUTE)) {
            return true;
        }

        return $this->countAttempts($signature, self::LAST_TEN_MINUTES) < self::MAX_ATTEMPTS;
    }

    public function countAttempts(string $signature, string $interval = self::LAST_TEN_MINUTES): int
    {
        return $this->createQueryBuilder('fla')
            ->select('count(fla.id)')
            ->where('fla.signature = :signature')
            ->andWhere('fla.at >= :startAt')
            ->setParameters([
                'signature' => $signature,
                'startAt' => $this->createDateTimeForInterval($interval),
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createDateTimeForInterval(string $interval): \DateTime
    {
        $interval = new \DateInterval($interval);
        $interval->invert = 1;

        $date = \DateTime::createFromFormat('U', time());
        $date->add($interval);

        return $date;
    }
}
