<?php
// src/Repository/PersonalDetailsRepository.php

namespace App\Repository;

use App\Entity\PersonalDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PersonalDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method PersonalDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method PersonalDetails[]    findAll()
 * @method PersonalDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PersonalDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalDetails::class);
    }

    /**
     * Find active personal details
     */
    public function findActive(): ?PersonalDetails
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active details ordered by creation date
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by email
     */
    public function findByEmail(string $email): ?PersonalDetails
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get latest updated details
     */
    public function findLatest(): ?PersonalDetails
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count active details
     */
    public function countActive(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}