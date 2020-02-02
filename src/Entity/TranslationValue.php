<?php

namespace Lordjancso\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="lj_translation_values", uniqueConstraints={@ORM\UniqueConstraint(columns={"key_id", "locale"})})
 * @ORM\Entity(repositoryClass="Lordjancso\TranslationBundle\Repository\TranslationValueRepository")
 * @UniqueEntity(fields={"key", "locale"})
 */
class TranslationValue
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
     * @ORM\Column(name="content", type="string", length=255, nullable=false)
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=255, nullable=false)
     */
    private $locale;

    /**
     * @var TranslationDomain
     *
     * @ORM\ManyToOne(targetEntity="TranslationDomain")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", nullable=false)
     */
    private $domain;

    /**
     * @var TranslationKey
     *
     * @ORM\ManyToOne(targetEntity="TranslationKey")
     * @ORM\JoinColumn(name="key_id", referencedColumnName="id", nullable=false)
     */
    private $key;

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
