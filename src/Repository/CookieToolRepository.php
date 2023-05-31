<?php

namespace Netzhirsch\CookieOptInBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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

    /**
     * @throws NonUniqueResultException
     */
    public function findOneBySourceIdAndUrl(array $sourceIds, string $url)
    {
        return $this->createQueryBuilder('c')
            ->where('c.i_frame_blocked_urls LIKE :url')
            ->setParameter('url', '%'.$url.'%')
            ->leftJoin('c.parent', 'parent')
            ->andWhere('parent.sourceId IN (:sourceIds)')
            ->setParameter('sourceIds', $sourceIds)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneBySourceIdAndType(array $sourceIds, string $iFrameType)
    {
        return $this->createQueryBuilder('c')
            ->where('c.cookieToolsSelect LIKE :iFrameType')
            ->setParameter('iFrameType', $iFrameType)
            ->leftJoin('c.parent', 'parent')
            ->andWhere('parent.sourceId IN (:sourceIds)')
            ->setParameter('sourceIds', $sourceIds)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
