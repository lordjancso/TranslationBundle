<?php

namespace Lordjancso\TranslationBundle\Tests\Service;

use Lordjancso\TranslationBundle\Command\ImportTranslationsCommand;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Service\TranslationImporter;
use Lordjancso\TranslationBundle\Service\TranslationManager;
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
        $commandTester = $this->getCommandTester(false);
        $commandTester->execute([]);

        $this->assertStringContainsString('The import command can only be executed safely on \'mysql\'.', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testExecuteNoTranslationFileFound(): void
    {
        $commandTester = $this->getCommandTester(true);
        $commandTester->execute([]);

        $this->assertStringContainsString('No translation file found in path', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testExecuteNotInManagedLocales(): void
    {
        (new Filesystem())->appendToFile(__DIR__.'/../_output/translations/domain.de.yaml', '\'key1\': \'trans1\'');

        $commandTester = $this->getCommandTester(true);
        $commandTester->execute([]);

        $this->assertStringContainsString('SKIP! Not in managed locales.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExecuteNoChanges(): void
    {
        (new Filesystem())->appendToFile(__DIR__.'/../_output/translations/domain.en.yaml', '\'key1\': \'trans1\'');

        $commandTester = $this->getCommandTester(true);
        $commandTester->execute([]);

        $this->assertStringContainsString('SKIP! No changes in the file.', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExecuteSuccess(): void
    {
        $importer = $this->createMock(TranslationImporter::class);
        $importer->method('importDomain')
            ->willReturnCallback(function () {
                $translationDomain = $this->createMock(TranslationDomain::class);
                $translationDomain->method('getId')
                    ->willReturn(1);
                $translationDomain->method('getName')
                    ->willReturn('domain');
                $translationDomain->method('getLocale')
                    ->willReturn('en');
                $translationDomain->method('getPath')
                    ->willReturn('x');

                return $translationDomain;
            });
        $importer->method('importKeys')
            ->willReturn(['some-key' => 'some-trans']);

        $commandTester = $this->getCommandTester(true, $importer);
        $commandTester->execute([]);

        $this->assertStringContainsString('SUCCESS!', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    private function getCommandTester($isPlatformSupported, $importer = null): CommandTester
    {
        $manager = $this->createMock(TranslationManager::class);
        $manager->method('isDatabasePlatformSupported')
            ->willReturn($isPlatformSupported);
        $manager->method('getManagedLocales')
            ->willReturn(['en']);

        if (null === $importer) {
            $importer = $this->createMock(TranslationImporter::class);
        }

        $importer->method('importValues')
            ->willReturn(true);

        $application = new Application();
        $application->add(new ImportTranslationsCommand($manager, $importer, __DIR__.'/../_output'));
        $command = $application->find('lordjancso:import-translations');
        $command->setApplication($application);

        return new CommandTester($command);
    }
}
