<?php

namespace Lordjancso\TranslationBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;

/**
 * @method TranslationDomain|null find($id, $lockMode = null, $lockVersion = null)
 * @method TranslationDomain|null findOneBy(array $criteria, array $orderBy = null)
 * @method TranslationDomain[]    findAll()
 * @method TranslationDomain[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TranslationDomainRepository extends EntityRepository
{
    public function findAllToExport(): array
    {
        return $this->createQueryBuilder('td')
            ->addOrderBy('td.name', 'ASC')
            ->addOrderBy('td.locale', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
