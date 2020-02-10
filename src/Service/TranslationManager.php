<?php

namespace Lordjancso\TranslationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

class TranslationManager
{
    protected $em;
    protected $managedLocales;

    public function __construct(EntityManagerInterface $em, array $managedLocales)
    {
        $this->em = $em;
        $this->managedLocales = $managedLocales;
    }

    public function updateTranslationsByKey(TranslationKey $translationKey, array $translations): void
    {
        foreach ($translations as $locale => $content) {
            if (empty($content)) {
                continue;
            }

            $translationValue = $this->em->getRepository(TranslationValue::class)->findOneBy([
                'key' => $translationKey,
                'locale' => $locale,
            ]);

            if ($translationValue instanceof TranslationValue && $translationValue->getContent() === $content) {
                continue;
            }

            if (!$translationValue instanceof TranslationValue) {
                $translationDomain = $this->em->getRepository(TranslationDomain::class)->findOneBy([
                    'name' => $translationKey->getDomain(),
                    'locale' => $locale,
                ]);

                $translationValue = (new TranslationValue())
                    ->setDomain($translationDomain)
                    ->setKey($translationKey)
                    ->setLocale($locale);

                $this->em->persist($translationValue);
            }

            $translationValue->setContent($content);
        }

        $this->em->flush();
    }
}
