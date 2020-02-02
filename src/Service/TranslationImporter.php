<?php

namespace Lordjancso\TranslationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationKey;

class TranslationImporter
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function importDomain(string $domain, string $locale, string $path): ?TranslationDomain
    {
        $translationDomain = $this->em->getRepository(TranslationDomain::class)->findOneBy([
            'name' => $domain,
            'locale' => $locale,
            'path' => $path,
        ]);

        if (!$translationDomain instanceof TranslationDomain) {
            $translationDomain = (new TranslationDomain())
                ->setName($domain)
                ->setLocale($locale)
                ->setPath($path);

            $this->em->persist($translationDomain);
        }

        $hash = hash_file('md5', $path);

        if ($hash === $translationDomain->getHash()) {
            return null;
        }

        $translationDomain->setHash($hash);

        $this->em->flush();

        return $translationDomain;
    }

    public function importKeys(TranslationDomain $translationDomain, array $yaml): array
    {
        $dbTranslationKeys = $this->em->getRepository(TranslationKey::class)->createQueryBuilder('tk')
            ->select('tk.id', 'tk.name')
            ->andWhere('tk.domain = :domain')
            ->setParameter('domain', $translationDomain->getName())
            ->addOrderBy('tk.id')
            ->indexBy('tk', 'tk.id')
            ->getQuery()
            ->getArrayResult();

        $dbTranslationKeys = array_map(function ($item) {
            return $item['name'];
        }, $dbTranslationKeys);

        $yamlTranslationKeys = array_keys($yaml);
        $newTranslationKeys = array_diff($yamlTranslationKeys, $dbTranslationKeys);
        $newTranslationKeyNames = [];

        foreach ($yaml as $keyName => $content) {
            if (!in_array($keyName, $newTranslationKeys, true)) {
                continue;
            }

            $newTranslationKeyNames[] = $keyName;
        }

        if (!empty($newTranslationKeyNames)) {
            $dbTranslationKeys = $this->em->getRepository(TranslationKey::class)->insertAndGet($translationDomain, $newTranslationKeyNames);
        }

        return $dbTranslationKeys;
    }
}
