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

    public function getDatabasePlatformName(): string
    {
        return $this->em->getConnection()->getDatabasePlatform()->getName();
    }

    public function getManagedLocales(): array
    {
        return $this->managedLocales;
    }

    public function insertOrUpdateTranslationValues(int $domainId, string $locale, array $contentsAndKeyIds): bool
    {
        $data = [];

        foreach ($contentsAndKeyIds as $keyId => $content) {
            $data[] = implode(',', ['\''.addslashes($content).'\'', '\''.$locale.'\'', $domainId, $keyId]);
        }

        $sql = 'INSERT INTO lj_translation_values (content, locale, domain_id, key_id) VALUES (';
        $sql .= implode('),(', $data).') ';
        $sql .= 'ON DUPLICATE KEY UPDATE content = VALUES(content)';

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

    public function updateTranslationsByKey(TranslationKey $translationKey, array $translations): bool
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

        return true;
    }
}
