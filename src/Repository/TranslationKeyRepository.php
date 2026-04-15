<?php

namespace Lordjancso\TranslationBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationKey;

/**
 * @method TranslationKey|null find($id, $lockMode = null, $lockVersion = null)
 * @method TranslationKey|null findOneBy(array $criteria, array $orderBy = null)
 * @method TranslationKey[]    findAll()
 * @method TranslationKey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TranslationKeyRepository extends EntityRepository
{
    // import queries

    public function insertAndGet(TranslationDomain $translationDomain, array $names, bool $isIgnore = false): array
    {
        $placeholders = [];
        $params = [];

        foreach ($names as $i => $name) {
            $placeholders[] = "(:name_{$i}, :domain_{$i}, NOW(), NOW())";
            $params["name_{$i}"] = $name;
            $params["domain_{$i}"] = $translationDomain->getName();
        }

        $ignore = $isIgnore ? 'IGNORE' : '';
        $onDuplicate = $isIgnore ? '' : 'ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = VALUES(updated_at)';

        $sql = 'INSERT '.$ignore.' INTO lj_translation_keys (name, domain, created_at, updated_at) VALUES '.implode(',', $placeholders).' '.$onDuplicate;

        $this->_em->getConnection()->executeStatement($sql, $params);

        return $this->getAllToImport($translationDomain);
    }

    public function getAllToImport(TranslationDomain $translationDomain): array
    {
        $dbTranslationKeys = $this->createQueryBuilder('tk')
            ->select('tk.id', 'tk.name')
            ->andWhere('tk.domain = :domain')
            ->setParameter('domain', $translationDomain->getName())
            ->addOrderBy('tk.id')
            ->indexBy('tk', 'tk.id')
            ->getQuery()
            ->getArrayResult();

        return array_map(function ($item) {
            return $item['name'];
        }, $dbTranslationKeys);
    }

    // other queries

    public function getStats(): array
    {
        $items = $this->createQueryBuilder('tk')
            ->select('COUNT(tk.id) AS count, tk.domain')
            ->groupBy('tk.domain')
            ->orderBy('tk.domain', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $stats = [];

        foreach ($items as $item) {
            $stats[$item['domain']] = (int) $item['count'];
        }

        return $stats;
    }
}
