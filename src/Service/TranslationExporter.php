<?php

namespace Lordjancso\TranslationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

class TranslationExporter
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getDomains(): array
    {
        return $this->em->getRepository(TranslationDomain::class)->findAllToExport();
    }

    public function exportDomain(int $domainId, string $domainName): array
    {
        $translations = [];
        $keys = $this->em->getRepository(TranslationKey::class)->findAllToExport($domainName);

        foreach ($keys as $key) {
            $values = $this->em->getRepository(TranslationValue::class)->findAllToExport($domainId, $key['id']);

            foreach ($values as $value) {
                $translations[$key['name']] = $value['content'];
            }
        }

        return $translations;
    }
}
