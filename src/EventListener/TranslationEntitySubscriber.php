<?php

namespace Lordjancso\TranslationBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Entity\TranslationValue;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class TranslationEntitySubscriber
{
    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if (!$entity instanceof TranslationKey && !$entity instanceof TranslationValue) {
            return;
        }

        $entity->setCreatedAt(new \DateTime());
        $entity->setUpdatedAt(new \DateTime());
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if (!$entity instanceof TranslationKey && !$entity instanceof TranslationValue) {
            return;
        }

        $entity->setUpdatedAt(new \DateTime());
    }
}
