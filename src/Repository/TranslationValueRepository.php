<?php

namespace Lordjancso\TranslationBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

/**
 * @method TranslationValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method TranslationValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method TranslationValue[]    findAll()
 * @method TranslationValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TranslationValueRepository extends EntityRepository
{
    public function findAllToExport(int $domainId, int $keyId): array
    {
        return $this->createQueryBuilder('tv')
            ->select('tv.content')
            ->andWhere('tv.domain = :domain')
            ->setParameter('domain', $domainId)
            ->andWhere('tv.key = :key')
            ->setParameter('key', $keyId)
            ->getQuery()
            ->getArrayResult();
    }
}
