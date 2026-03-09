<?php

namespace Lordjancso\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Lordjancso\TranslationBundle\Repository\TranslationDomainRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: 'lj_translation_domains')]
#[ORM\UniqueConstraint(columns: ['name', 'locale'])]
#[ORM\Entity(repositoryClass: TranslationDomainRepository::class)]
#[UniqueEntity(fields: ['name', 'locale'])]
class TranslationDomain
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    #[ORM\Column(name: 'locale', type: 'string', length: 255, nullable: false)]
    private ?string $locale = null;

    #[ORM\Column(name: 'path', type: 'string', length: 255, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(name: 'hash', type: 'string', length: 255, nullable: true)]
    private ?string $hash = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }
}
