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
    public function findAllToExport(string $domain): array
    {
        return $this->createQueryBuilder('tk')
            ->select('tk.id', 'tk.name')
            ->andWhere('tk.domain = :domain')
            ->setParameter('domain', $domain)
            ->addOrderBy('tk.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function insertAndGet(TranslationDomain $translationDomain, array $names, bool $isIgnore = false): array
    {
        foreach ($names as $i => $name) {
            $names[$i] = "'{$name}','{$translationDomain->getName()}'";
        }

        $ignore = $isIgnore
            ? 'IGNORE'
            : '';

        $onDuplicate = $isIgnore
            ? ''
            : 'ON DUPLICATE KEY UPDATE name = VALUES(name)';

        $sql = 'INSERT '.$ignore.' INTO lj_translation_keys (name, domain) VALUES ('.implode('),(', $names).') '.$onDuplicate;

        $this->_em->getConnection()->executeQuery($sql);

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
}
