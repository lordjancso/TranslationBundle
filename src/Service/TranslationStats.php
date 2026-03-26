<?php

namespace Lordjancso\TranslationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

class TranslationStats
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly array $managedLocales,
    ) {
    }

    public function getStats(): array
    {
        $stats = [];
        $keyStats = $this->em->getRepository(TranslationKey::class)->getStats();
        $allValueStats = $this->em->getRepository(TranslationValue::class)->getAllStats();

        foreach ($keyStats as $domain => $keyCount) {
            $stat = [];

            foreach ($this->managedLocales as $locale) {
                $translated = $allValueStats[$domain][$locale] ?? 0;

                $stat[$locale] = [
                    'keys' => $keyCount,
                    'translated' => $translated,
                    'percent' => $keyCount > 0 ? (int) floor($translated / $keyCount * 100) : 0,
                ];
            }

            $stats[$domain] = $stat;
        }

        return $stats;
    }
}
