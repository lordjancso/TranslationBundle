<?php

namespace Lordjancso\TranslationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

class TranslationStats
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected array $managedLocales,
    ) {
    }

    public function getStats(): array
    {
        $stats = [];
        $keyStats = $this->em->getRepository(TranslationKey::class)->getStats();

        foreach ($keyStats as $domain => $keyCount) {
            $stat = [];
            $valueStats = $this->em->getRepository(TranslationValue::class)->getStats($domain);

            foreach ($this->managedLocales as $locale) {
                $translated = $valueStats[$locale] ?? 0;

                $stat[$locale] = [
                    'keys' => $keyCount,
                    'translated' => $translated,
                    'percent' => $keyCount > 0 ? floor($translated / $keyCount * 100) : 0,
                ];
            }

            $stats[$domain] = $stat;
        }

        return $stats;
    }
}
