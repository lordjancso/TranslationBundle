<?php

namespace Lordjancso\TranslationBundle\Translation;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DatabaseLoader implements LoaderInterface
{
    private ?array $cache = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        if (null === $this->cache) {
            $this->loadAll();
        }

        $catalogue = new MessageCatalogue($locale);

        foreach ($this->cache[$locale][$domain] ?? [] as $key => $content) {
            $catalogue->set($key, $content, $domain);
        }

        return $catalogue;
    }

    private function loadAll(): void
    {
        $this->cache = [];

        $rows = $this->em->getConnection()->fetchAllAssociative(
            'SELECT tk.name AS `key`, tv.content, tk.domain, tv.locale
             FROM lj_translation_values tv
             INNER JOIN lj_translation_keys tk ON tv.key_id = tk.id'
        );

        foreach ($rows as $row) {
            $this->cache[$row['locale']][$row['domain']][$row['key']] = $row['content'];
        }
    }
}
