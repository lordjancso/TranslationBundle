<?php

namespace Lordjancso\TranslationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

class TranslationExporter
{
    public function __construct(
        protected EntityManagerInterface $em,
    ) {
    }

    public function getDomains(): array
    {
        return $this->em->getRepository(TranslationDomain::class)->findAllToExport();
    }

    public function exportDomain(string $domainName, string $locale): array
    {
        $rows = $this->em->getRepository(TranslationValue::class)->getAllByDomainAndLocale($domainName, $locale);

        $translations = [];

        foreach ($rows as $row) {
            $translations[$row['key']] = $row['content'];
        }

        return $translations;
    }
}
