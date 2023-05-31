<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Netzhirsch\CookieOptInBundle\Entity\CookieToolContainer;

/**
 * @extends ServiceEntityRepository<CookieToolContainer>
 *
 * @method CookieToolContainer|null find($id, $lockMode = null, $lockVersion = null)
 * @method CookieToolContainer|null findOneBy(array $criteria, array $orderBy = null)
 * @method CookieToolContainer[]    findAll()
 * @method CookieToolContainer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CookieToolContainerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CookieToolContainer::class);
    }

    public function save(CookieToolContainer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CookieToolContainer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return CookieToolContainer[] Returns an array of CookieToolContainer objects
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

//    public function findOneBySomeField($value): ?CookieToolContainer
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

}
