<?php

namespace Lordjancso\TranslationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="lj_translation_keys", uniqueConstraints={@ORM\UniqueConstraint(columns={"name", "domain"})})
 * @ORM\Entity(repositoryClass="Lordjancso\TranslationBundle\Repository\TranslationKeyRepository")
 * @UniqueEntity(fields={"name", "domain"})
 */
class TranslationKey
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=500, nullable=false, options={"collation":"utf8_bin"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=255, nullable=false)
     */
    private $domain;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

    /**
     * @var TranslationValue[]
     *
     * @ORM\OneToMany(targetEntity="TranslationValue", mappedBy="key", cascade={"remove"})
     */
    private $translations;

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
