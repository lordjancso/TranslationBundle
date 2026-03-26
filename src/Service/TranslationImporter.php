<?php

namespace Lordjancso\TranslationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

class TranslationImporter
{
    public function __construct(
        protected EntityManagerInterface $em,
    ) {
    }

    public function importDomain(string $domain, string $locale, string $path, string $absolutePath): ?TranslationDomain
    {
        $translationDomain = $this->em->getRepository(TranslationDomain::class)->findOneBy([
            'name' => $domain,
            'locale' => $locale,
        ]);

        if (!$translationDomain instanceof TranslationDomain) {
            $translationDomain = (new TranslationDomain())
                ->setName($domain)
                ->setLocale($locale);

            $this->em->persist($translationDomain);
        }

        $translationDomain->setPath($path);

        $hash = hash_file('md5', $absolutePath);

        if ($hash === $translationDomain->getHash()) {
            return null;
        }

        $translationDomain->setHash($hash);

        $this->em->flush();

        return $translationDomain;
    }

    public function importKeys(TranslationDomain $translationDomain, array $yaml): array
    {
        $normalizedKeys = array_map(fn ($key) => \Normalizer::normalize((string) $key, \Normalizer::FORM_C), array_keys($yaml));

        $dbTranslationKeys = $this->em->getRepository(TranslationKey::class)->getAllToImport($translationDomain);

        $newTranslationKeys = array_diff($normalizedKeys, $dbTranslationKeys);

        if (!empty($newTranslationKeys)) {
            $dbTranslationKeys = $this->em->getRepository(TranslationKey::class)->insertAndGet($translationDomain, array_values($newTranslationKeys));
        }

        return $dbTranslationKeys;
    }

    public function importValues(int $domainId, string $locale, array $contentsAndKeyIds, int $batchSize = 500): bool
    {
        $batches = array_chunk($contentsAndKeyIds, $batchSize, true);

        foreach ($batches as $batch) {
            $placeholders = [];
            $params = [];
            $i = 0;

            foreach ($batch as $keyId => $content) {
                $placeholders[] = "(:content_{$i}, :locale_{$i}, :domain_id_{$i}, :key_id_{$i}, NOW(), NOW())";
                $params["content_{$i}"] = \Normalizer::normalize((string) $content, \Normalizer::FORM_C);
                $params["locale_{$i}"] = $locale;
                $params["domain_id_{$i}"] = $domainId;
                $params["key_id_{$i}"] = $keyId;
                ++$i;
            }

            $sql = 'INSERT INTO lj_translation_values (content, locale, domain_id, key_id, created_at, updated_at) VALUES ';
            $sql .= implode(',', $placeholders);
            $sql .= ' ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = VALUES(updated_at)';

            $this->em->getConnection()->executeStatement($sql, $params);
        }

        return true;
    }

    public function deleteAllTranslationValues(TranslationDomain $translationDomain, array $dbTranslationKeys): bool
    {
        $this->em->getRepository(TranslationValue::class)->deleteAllByDomainAndKey($translationDomain, $dbTranslationKeys);

        // delete translation keys without translation value
        $sql = 'DELETE FROM lj_translation_keys tk WHERE NOT EXISTS (SELECT id FROM lj_translation_values tv WHERE tv.key_id = tk.id)';
        $this->em->getConnection()->executeStatement($sql);

        return true;
    }
}
