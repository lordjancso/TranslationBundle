<?php

namespace Lordjancso\TranslationBundle\EventListener;

use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

class TranslationEntityListener
{
    public function prePersist(TranslationKey|TranslationValue $entity): void
    {
        $entity->setCreatedAt(new \DateTime());
        $entity->setUpdatedAt(new \DateTime());
    }

    public function preUpdate(TranslationKey|TranslationValue $entity): void
    {
        $entity->setUpdatedAt(new \DateTime());
    }
}
