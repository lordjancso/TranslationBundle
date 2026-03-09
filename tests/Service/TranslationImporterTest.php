<?php

namespace Lordjancso\TranslationBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Repository\TranslationDomainRepository;
use Lordjancso\TranslationBundle\Repository\TranslationKeyRepository;
use Lordjancso\TranslationBundle\Service\TranslationImporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class TranslationImporterTest extends TestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public static function setUpBeforeClass(): void
    {
        (new Filesystem())->mkdir(__DIR__.'/../_output/translations');
        (new Filesystem())->touch(__DIR__.'/../_output/translations/existing-domain.en.yaml');
        (new Filesystem())->touch(__DIR__.'/../_output/translations/new-domain.en.yaml');
    }

    public static function tearDownAfterClass(): void
    {
        (new Filesystem())->remove(__DIR__.'/../_output');
    }

    protected function setUp(): void
    {
        $translationDomainRepository = $this->createMock(TranslationDomainRepository::class);
        $translationDomainRepository->method('findOneBy')
            ->willReturnCallback(function ($criterias) {
                $existing = [
                    'name' => 'existing-domain',
                    'locale' => 'en',
                    'path' => __DIR__.'/../_output/translations/existing-domain.en.yaml',
                ];

                if ($criterias === $existing) {
                    return (new TranslationDomain())
                        ->setName('existing-domain')
                        ->setLocale('en')
                        ->setPath(__DIR__.'/../_output/translations/existing-domain.en.yaml')
                        ->setHash('d41d8cd98f00b204e9800998ecf8427e');
                } else {
                    return null;
                }
            });

        $translationKeyRepository = $this->createMock(TranslationKeyRepository::class);
        $translationKeyRepository->method('getAllToImport')
            ->willReturn(['name']);
        $translationKeyRepository->method('insertAndGet')
            ->willReturn(['new']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturnCallback(function ($class) use ($translationDomainRepository, $translationKeyRepository) {
                if (TranslationDomain::class === $class) {
                    return $translationDomainRepository;
                } else {
                    return $translationKeyRepository;
                }
            });

        $this->em = $entityManager;
    }

    /**
     * @dataProvider importDomainDataProvider
     */
    public function testImportDomain($expected, $domain, $locale, $path): void
    {
        $service = new TranslationImporter($this->em);

        $this->assertEquals($expected, $service->importDomain($domain, $locale, $path));
    }

    public function importDomainDataProvider()
    {
        return [
            'Existing translation domain' => [
                null,
                'existing-domain',
                'en',
                __DIR__.'/../_output/translations/existing-domain.en.yaml',
            ],
            'New translation domain' => [
                (new TranslationDomain())
                    ->setName('new-domain')
                    ->setLocale('en')
                    ->setPath(__DIR__.'/../_output/translations/new-domain.en.yaml')
                    ->setHash('d41d8cd98f00b204e9800998ecf8427e'),
                'new-domain',
                'en',
                __DIR__.'/../_output/translations/new-domain.en.yaml',
            ],
        ];
    }

    /**
     * @dataProvider importKeysDataProvider
     */
    public function testImportKeys($expected, $translationDomain, $yaml): void
    {
        $service = new TranslationImporter($this->em);

        $this->assertEquals($expected, $service->importKeys($translationDomain, $yaml));
    }

    public function importKeysDataProvider()
    {
        return [
            'Empty yaml, no new translations' => [
                ['name'],
                (new TranslationDomain())
                    ->setName('name'),
                [],
            ],
            'Yaml with new translations' => [
                ['new'],
                (new TranslationDomain())
                    ->setName('name'),
                [
                    'name' => 'name-trans',
                    'new' => 'new-trans',
                ],
            ],
        ];
    }
}
