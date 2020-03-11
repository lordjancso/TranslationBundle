<?php

namespace Lordjancso\TranslationBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
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

    public function deleteAllByDomainAndKey(TranslationDomain $translationDomain, array $translationKeys)
    {
        return $this->createQueryBuilder('tv')
            ->delete()
            ->andWhere('tv.domain = :domain')
            ->setParameter('domain', $translationDomain)
            ->andWhere('tv.key IN (:translationKeys)')
            ->setParameter('translationKeys', $translationKeys)
            ->getQuery()
            ->execute();
    }

    public function getAllByDomainAndLocale(string $domain, string $locale): array
    {
        return $this->createQueryBuilder('tv')
            ->select('tk.name AS key', 'tv.content')
            ->innerJoin('tv.key', 'tk')
            ->innerJoin('tv.domain', 'td')
            ->andWhere('tk.domain = :domain')
            ->andWhere('td.name = :domain')
            ->setParameter('domain', $domain)
            ->andWhere('td.locale = :locale')
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getArrayResult();
    }

    public function getStats(string $domain): array
    {
        $items = $this->createQueryBuilder('tv')
            ->select('COUNT(DISTINCT tv.id) AS count, tv.locale')
            ->innerJoin('tv.key', 'tk')
            ->andWhere('tk.domain = :domain')
            ->setParameter('domain', $domain)
            ->groupBy('tv.locale')
            ->getQuery()
            ->getResult();

        $stats = [];

        foreach ($items as $item) {
            $stats[$item['locale']] = (int) $item['count'];
        }

        return $stats;
    }
}
