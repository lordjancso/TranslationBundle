<?php

namespace Lordjancso\TranslationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

class TranslationStats
{
    protected $em;
    protected $managedLocales;

    public function __construct(EntityManagerInterface $em, array $managedLocales)
    {
        $this->em = $em;
        $this->managedLocales = $managedLocales;
    }

    public function getStats(): array
    {
        $stats = [];
        $keyStats = $this->em->getRepository(TranslationKey::class)->getStats();

        foreach ($keyStats as $domain => $keyCount) {
            $stat = [];
            $valueStats = $this->em->getRepository(TranslationValue::class)->getStats($domain);

            foreach ($this->managedLocales as $locale) {
                $stat[$locale] = [
                    'keys' => $keyCount,
                    'translated' => isset($valueStats[$locale])
                        ? $valueStats[$locale]
                        : 0,
                ];
            }

            $stats[$domain] = $stat;
        }

        return $stats;
    }
}
