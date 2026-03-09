<?php

namespace Lordjancso\TranslationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Lordjancso\TranslationBundle\Repository\TranslationKeyRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: 'lj_translation_keys')]
#[ORM\UniqueConstraint(columns: ['name', 'domain'])]
#[ORM\Entity(repositoryClass: TranslationKeyRepository::class)]
#[UniqueEntity(fields: ['name', 'domain'])]
class TranslationKey
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 500, nullable: false, options: ['collation' => 'utf8_bin'])]
    private ?string $name = null;

    #[ORM\Column(name: 'domain', type: 'string', length: 255, nullable: false)]
    private ?string $domain = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var ArrayCollection|TranslationValue[]
     */
    #[ORM\OneToMany(mappedBy: 'key', targetEntity: 'TranslationValue', cascade: ['remove'])]
    private ?Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

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

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

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

    public function addTranslation(TranslationValue $translationValue): self
    {
        $this->translations->add($translationValue);

        return $this;
    }

    public function removeTranslation(TranslationValue $translationValue): self
    {
        $this->translations->removeElement($translationValue);

        return $this;
    }

    /**
     * @return ArrayCollection|TranslationValue[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }
}
