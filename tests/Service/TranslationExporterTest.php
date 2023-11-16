<?php

namespace Lordjancso\TranslationBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationKey;
use Lordjancso\TranslationBundle\Repository\TranslationDomainRepository;
use Lordjancso\TranslationBundle\Repository\TranslationKeyRepository;
use Lordjancso\TranslationBundle\Repository\TranslationValueRepository;
use Lordjancso\TranslationBundle\Service\TranslationExporter;
use PHPUnit\Framework\TestCase;

class TranslationExporterTest extends TestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    protected function setUp(): void
    {
        $translationDomainRepository = $this->createMock(TranslationDomainRepository::class);
        $translationDomainRepository->method('findAllToExport')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'domain1',
                    'locale' => 'en',
                    'path' => 'some/path/to/file.yaml',
                    'hash' => 'iddqd',
                ],
            ]);

        $translationKeyRepository = $this->createMock(TranslationKeyRepository::class);
        $translationKeyRepository->method('findAllToExport')
            ->with('domain1')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'key1',
                ],
            ]);

        $translationValueRepository = $this->createMock(TranslationValueRepository::class);
        $translationValueRepository->method('findAllToExport')
            ->with(1, 1)
            ->willReturn([
                [
                    'content' => 'Key 1 translation',
                ],
            ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturnCallback(function ($class) use ($translationDomainRepository, $translationKeyRepository, $translationValueRepository) {
                if (TranslationDomain::class === $class) {
                    return $translationDomainRepository;
                } elseif (TranslationKey::class === $class) {
                    return $translationKeyRepository;
                } else {
                    return $translationValueRepository;
                }
            });

        $this->em = $entityManager;
    }

    public function testGetDomains(): void
    {
        $service = new TranslationExporter($this->em);

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'domain1',
                'locale' => 'en',
                'path' => 'some/path/to/file.yaml',
                'hash' => 'iddqd',
            ],
        ], $service->getDomains());
    }

    public function testExportDomain(): void
    {
        $service = new TranslationExporter($this->em);

        $this->assertSame([
            'key1' => 'Key 1 translation',
        ], $service->exportDomain(1, 'domain1'));
    }
}
