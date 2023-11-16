<?php

namespace Lordjancso\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Lordjancso\TranslationBundle\Repository\TranslationValueRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: 'lj_translation_values')]
#[ORM\UniqueConstraint(columns: ['key_id', 'locale'])]
#[ORM\Entity(repositoryClass: TranslationValueRepository::class)]
#[UniqueEntity(fields: ['key', 'locale'])]
class TranslationValue
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'content', type: 'string', length: 500, nullable: false)]
    private ?string $content = null;

    #[ORM\Column(name: 'locale', type: 'string', length: 255, nullable: false)]
    private ?string $locale = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: 'TranslationDomain')]
    #[ORM\JoinColumn(name: 'domain_id', referencedColumnName: 'id', nullable: false)]
    private ?TranslationDomain $domain = null;

    #[ORM\ManyToOne(targetEntity: 'TranslationKey', inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'key_id', referencedColumnName: 'id', nullable: false)]
    private ?TranslationKey $key = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDomain(): ?TranslationDomain
    {
        return $this->domain;
    }

    public function setDomain(TranslationDomain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getKey(): ?TranslationKey
    {
        return $this->key;
    }

    public function setKey(TranslationKey $key): self
    {
        $this->key = $key;

        return $this;
    }
}
