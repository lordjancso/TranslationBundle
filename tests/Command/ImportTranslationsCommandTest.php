<?php

namespace Lordjancso\TranslationBundle\Tests\Command;

use Lordjancso\TranslationBundle\Command\ImportTranslationsCommand;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Service\TranslationImporter;
use Lordjancso\TranslationBundle\Service\TranslationManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ImportTranslationsCommandTest extends TestCase
{
    private const OUTPUT_DIR = __DIR__.'/../_output';

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->filesystem->remove(self::OUTPUT_DIR);
        $this->filesystem->mkdir(self::OUTPUT_DIR.'/translations');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(self::OUTPUT_DIR);
    }

    public function testFailsWhenNotMysql(): void
    {
        $commandTester = $this->createCommandTester(false);
        $commandTester->execute([]);

        $this->assertStringContainsString('The import command can only be executed safely on \'mysql\'.', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testFailsWhenNoFilesFound(): void
    {
        $commandTester = $this->createCommandTester(true);
        $commandTester->execute([]);

        $this->assertStringContainsString('No translation file found in path', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testSkipsLocaleNotInManagedLocales(): void
    {
        $this->createYamlFile('domain.de.yaml', "'key1': 'trans1'");

        $commandTester = $this->createCommandTester(true);
        $commandTester->execute([]);

        $this->assertStringContainsString('SKIP! Not in managed locales.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testSkipsWhenNoChanges(): void
    {
        $this->createYamlFile('domain.en.yaml', "'key1': 'trans1'");

        $commandTester = $this->createCommandTester(true);
        $commandTester->execute([]);

        $this->assertStringContainsString('SKIP! No changes in the file.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testImportsSuccessfully(): void
    {
        $this->createYamlFile('domain.en.yaml', "'key1': 'trans1'");

        $importer = $this->createMock(TranslationImporter::class);
        $importer->method('importDomain')
            ->willReturnCallback(function () {
                $domain = $this->createMock(TranslationDomain::class);
                $domain->method('getId')->willReturn(1);

                return $domain;
            });
        $importer->method('importKeys')
            ->willReturn(['some-key' => 'some-trans']);
        $importer->method('importValues')
            ->willReturn(true);

        $commandTester = $this->createCommandTester(true, $importer);
        $commandTester->execute([]);

        $this->assertStringContainsString('SUCCESS!', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testConfirmationWhenTablesNotEmpty(): void
    {
        $this->createYamlFile('domain.en.yaml', "'key1': 'trans1'");

        $commandTester = $this->createCommandTester(true, null, false);
        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $this->assertStringContainsString('Translation tables are not empty', $commandTester->getDisplay());
        $this->assertStringContainsString('Import cancelled.', $commandTester->getDisplay());
    }

    public function testForceSkipsConfirmation(): void
    {
        $this->createYamlFile('domain.en.yaml', "'key1': 'trans1'");

        $commandTester = $this->createCommandTester(true, null, false);
        $commandTester->execute(['--force' => true]);

        $this->assertStringNotContainsString('Translation tables are not empty', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testTruncateOption(): void
    {
        $this->createYamlFile('domain.en.yaml', "'key1': 'trans1'");

        $manager = $this->createMock(TranslationManager::class);
        $manager->method('isDatabasePlatformSupported')->willReturn(true);
        $manager->method('getManagedLocales')->willReturn(['en']);
        $manager->expects($this->once())->method('truncate');
        $importer = $this->createMock(TranslationImporter::class);
        $importer->method('importDomain')
            ->willReturnCallback(function () {
                $domain = $this->createMock(TranslationDomain::class);
                $domain->method('getId')->willReturn(1);

                return $domain;
            });
        $importer->method('importKeys')->willReturn([]);
        $importer->method('importValues')->willReturn(true);

        $command = new ImportTranslationsCommand($manager, $importer, self::OUTPUT_DIR);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--truncate' => true]);

        $this->assertStringContainsString('All translation tables truncated.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    private function createCommandTester(bool $isPlatformSupported, ?TranslationImporter $importer = null, bool $tablesEmpty = true): CommandTester
    {
        $manager = $this->createMock(TranslationManager::class);
        $manager->method('isDatabasePlatformSupported')->willReturn($isPlatformSupported);
        $manager->method('getManagedLocales')->willReturn(['en']);
        $manager->method('isTablesEmpty')->willReturn($tablesEmpty);

        if (null === $importer) {
            $importer = $this->createMock(TranslationImporter::class);
            $importer->method('importValues')->willReturn(true);
        }

        return new CommandTester(new ImportTranslationsCommand($manager, $importer, self::OUTPUT_DIR));
    }

    private function createYamlFile(string $filename, string $content): void
    {
        file_put_contents(self::OUTPUT_DIR.'/translations/'.$filename, $content);
    }
}
