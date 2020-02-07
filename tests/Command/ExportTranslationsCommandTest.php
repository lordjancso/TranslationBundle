<?php

namespace Lordjancso\TranslationBundle\Tests\Service;

use Lordjancso\TranslationBundle\Command\ExportTranslationsCommand;
use Lordjancso\TranslationBundle\Service\TranslationExporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ExportTranslationsCommandTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        (new Filesystem())->remove(__DIR__.'/../_output');
    }

    public static function tearDownAfterClass(): void
    {
        (new Filesystem())->remove(__DIR__.'/../_output');
    }

    public function testExecuteWithNoDomains(): void
    {
        $exporter = $this->createMock(TranslationExporter::class);
        $exporter->method('getDomains')
            ->willReturn([]);

        $commandTester = $this->getCommandTester($exporter);
        $commandTester->execute([]);

        $this->assertStringContainsString('No translation found in the database.', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testExecuteWithoutLocalTranslations(): void
    {
        $exporter = $this->createMock(TranslationExporter::class);
        $exporter->method('getDomains')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'domain1',
                    'locale' => 'en',
                    'path' => 'dummy/domain1.en.yaml',
                ],
            ]);
        $exporter->method('exportDomain')
            ->willReturn([
                'key1' => 'Value1',
                'key2' => 'Value2',
            ]);

        $this->assertFileNotExists(__DIR__.'/../_output/dummy/domain1.en.yaml');

        $commandTester = $this->getCommandTester($exporter);
        $commandTester->execute([]);

        $this->assertStringContainsString('domain1.en', $commandTester->getDisplay());
        $this->assertStringContainsString('Old: 0', $commandTester->getDisplay());
        $this->assertStringContainsString('New: 2', $commandTester->getDisplay());

        $this->assertFileExists(__DIR__.'/../_output/dummy/domain1.en.yaml');
        $this->assertStringEqualsFile(__DIR__.'/../_output/dummy/domain1.en.yaml', Yaml::dump([
            'key1' => 'Value1',
            'key2' => 'Value2',
        ]));
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithLocalTranslations(): void
    {
        $exporter = $this->createMock(TranslationExporter::class);
        $exporter->method('getDomains')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'domain1',
                    'locale' => 'en',
                    'path' => 'dummy/domain1.en.yaml',
                ],
            ]);
        $exporter->method('exportDomain')
            ->willReturn([
                'key1' => 'Value1',
                'newKey1' => 'New Value 1',
            ]);

        $this->assertFileExists(__DIR__.'/../_output/dummy/domain1.en.yaml');

        $commandTester = $this->getCommandTester($exporter);
        $commandTester->execute([]);

        $this->assertStringContainsString('domain1.en', $commandTester->getDisplay());
        $this->assertStringContainsString('Old: 2', $commandTester->getDisplay());
        $this->assertStringContainsString('New: 2', $commandTester->getDisplay());

        $this->assertFileExists(__DIR__.'/../_output/dummy/domain1.en.yaml');
        $this->assertStringEqualsFile(__DIR__.'/../_output/dummy/domain1.en.yaml', Yaml::dump([
            'key1' => 'Value1',
            'key2' => 'Value2',
            'newKey1' => 'New Value 1',
        ]));
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    private function getCommandTester($exporter): CommandTester
    {
        $application = new Application();
        $application->add(new ExportTranslationsCommand($exporter, new Filesystem(), __DIR__.'/../_output'));
        $command = $application->find('lordjancso:export-translations');
        $command->setApplication($application);

        return new CommandTester($command);
    }
}
