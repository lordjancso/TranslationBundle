<?php

namespace Lordjancso\TranslationBundle\Tests\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Command\ImportTranslationsCommand;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationValue;
use Lordjancso\TranslationBundle\Repository\TranslationValueRepository;
use Lordjancso\TranslationBundle\Service\TranslationImporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ImportTranslationsCommandTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        (new Filesystem())->mkdir(__DIR__.'/../_output/translations');
    }

    public static function tearDownAfterClass(): void
    {
        (new Filesystem())->remove(__DIR__.'/../_output');
    }

    public function testExecuteWithNoMysql(): void
    {
        $importer = $this->createMock(TranslationImporter::class);

        $commandTester = $this->getCommandTester($importer, 'pgsql');
        $commandTester->execute([]);

        $this->assertStringContainsString('The import command can only be executed safely on \'mysql\'.', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testExecuteNoTranslationFileFound(): void
    {
        $importer = $this->createMock(TranslationImporter::class);

        $commandTester = $this->getCommandTester($importer, 'mysql');
        $commandTester->execute([]);

        $this->assertStringContainsString('No translation file found in path', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testExecuteNotInManagedLocales(): void
    {
        (new Filesystem())->appendToFile(__DIR__.'/../_output/translations/domain.de.yaml', '\'key1\': \'trans1\'');

        $importer = $this->createMock(TranslationImporter::class);

        $commandTester = $this->getCommandTester($importer, 'mysql');
        $commandTester->execute([]);

        $this->assertStringContainsString('SKIP! Not in managed locales.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExecuteNoChanges(): void
    {
        (new Filesystem())->appendToFile(__DIR__.'/../_output/translations/domain.en.yaml', '\'key1\': \'trans1\'');

        $importer = $this->createMock(TranslationImporter::class);

        $commandTester = $this->getCommandTester($importer, 'mysql');
        $commandTester->execute([]);

        $this->assertStringContainsString('SKIP! No changes in the file.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExecuteSuccess(): void
    {
        $importer = $this->createMock(TranslationImporter::class);
        $importer->method('importDomain')
            ->willReturn(
                (new TranslationDomain())
                    ->setName('domain')
                    ->setLocale('en')
                    ->setPath('x')
            );
        $importer->method('importKeys')
            ->willReturn(['some-key' => 'some-trans']);

        $commandTester = $this->getCommandTester($importer, 'mysql');
        $commandTester->execute([]);

        $this->assertStringContainsString('SUCCESS!', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    private function getCommandTester($importer, $platformName): CommandTester
    {
        $translationValueRepository = $this->createMock(TranslationValueRepository::class);
        $translationValueRepository->method('deleteAllByDomainAndKey')
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')
            ->willReturnCallback(function () use ($platformName) {
                $connection = $this->createMock(Connection::class);
                $connection->method('getDatabasePlatform')
                    ->willReturnCallback(function () use ($platformName) {
                        $platform = $this->createMock(AbstractPlatform::class);
                        $platform->method('getName')
                            ->willReturn($platformName);

                        return $platform;
                    });

                return $connection;
            });
        $em->method('getRepository')
            ->willReturnCallback(function ($class) use ($translationValueRepository) {
                if (TranslationValue::class === $class) {
                    return $translationValueRepository;
                }

                return null;
            });

        $application = new Application();
        $application->add(new ImportTranslationsCommand($importer, $em, __DIR__.'/../_output', ['en']));
        $command = $application->find('lordjancso:import-translations');
        $command->setApplication($application);

        return new CommandTester($command);
    }
}
