<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Netzhirsch\CookieOptInBundle\Entity\CookieTool;

/**
 * @extends ServiceEntityRepository<CookieTool>
 *
 * @method CookieTool|null find($id, $lockMode = null, $lockVersion = null)
 * @method CookieTool|null findOneBy(array $criteria, array $orderBy = null)
 * @method CookieTool[]    findAll()
 * @method CookieTool[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CookieToolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CookieTool::class);
    }

    public function save(CookieTool $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CookieTool $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return CookieTool[] Returns an array of CookieTool objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CookieTool
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
