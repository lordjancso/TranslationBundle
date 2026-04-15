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
            ->andWhere('tk.domain = :domain')
            ->setParameter('domain', $domain)
            ->andWhere('tv.locale = :locale')
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getArrayResult();
    }

    public function getStats(string $domain): array
    {
        $items = $this->createQueryBuilder('tv')
            ->select('COUNT(tv.id) AS count, tv.locale')
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

    public function getAllStats(): array
    {
        $items = $this->createQueryBuilder('tv')
            ->select('tk.domain, tv.locale, COUNT(tv.id) AS count')
            ->innerJoin('tv.key', 'tk')
            ->groupBy('tk.domain, tv.locale')
            ->getQuery()
            ->getResult();

        $stats = [];

        foreach ($items as $item) {
            $stats[$item['domain']][$item['locale']] = (int) $item['count'];
        }

        return $stats;
    }
}
