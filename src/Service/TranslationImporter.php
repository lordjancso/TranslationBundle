<?php

namespace Lordjancso\TranslationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

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

    public function importValues(int $domainId, string $locale, array $contentsAndKeyIds): bool
    {
        $data = [];

        foreach ($contentsAndKeyIds as $keyId => $content) {
            $data[] = implode(',', [
                '\''.addslashes($content).'\'',
                '\''.$locale.'\'',
                $domainId,
                $keyId,
                'NOW()',
                'NOW()',
            ]);
        }

        $sql = 'INSERT INTO lj_translation_values (content, locale, domain_id, key_id, created_at, updated_at) VALUES (';
        $sql .= implode('),(', $data).') ';
        $sql .= 'ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = VALUES(updated_at)';

        $this->em->getConnection()->executeQuery($sql);

        return true;
    }

    public function deleteAllTranslationValues(TranslationDomain $translationDomain, array $dbTranslationKeys): bool
    {
        $this->em->getRepository(TranslationValue::class)->deleteAllByDomainAndKey($translationDomain, $dbTranslationKeys);

        // delete translation keys without translation value
        $sql = 'DELETE FROM lj_translation_keys tk WHERE NOT EXISTS (SELECT id FROM lj_translation_values tv WHERE tv.key_id = tk.id)';
        $this->em->getConnection()->executeQuery($sql);

        return true;
    }
}
