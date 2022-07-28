<?php

namespace Lordjancso\TranslationBundle\Translation;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationValue;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DatabaseLoader implements LoaderInterface
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);
        $translations = $this->em->getRepository(TranslationValue::class)->getAllByDomainAndLocale($domain, $locale);

        foreach ($translations as $translation) {
            $catalogue->set($translation['key'], $translation['content'], $domain);
        }

        return $catalogue;
    }
}
