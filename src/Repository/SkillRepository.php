<?php

namespace App\Repository;

use App\Entity\Skill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    public function save(Skill $skill, bool $flush = true): void
    {
        $this->getEntityManager()->persist($skill);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Skill $skill, bool $flush = true): void
    {
        $this->getEntityManager()->remove($skill);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveSkills()
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.displayOrder', 'ASC')
            ->addOrderBy('s.percentage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category)
    {
        return $this->createQueryBuilder('s')
            ->where('s.category = :category')
            ->andWhere('s.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('s.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}