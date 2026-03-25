<?php

namespace Lordjancso\TranslationBundle\Tests\Command;

use Lordjancso\TranslationBundle\Command\ExportTranslationsCommand;
use Lordjancso\TranslationBundle\Service\TranslationExporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ExportTranslationsCommandTest extends TestCase
{
    private const OUTPUT_DIR = __DIR__.'/../_output';

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->filesystem->remove(self::OUTPUT_DIR);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(self::OUTPUT_DIR);
    }

    public function testFailsWhenNoDomains(): void
    {
        $exporter = $this->createMock(TranslationExporter::class);
        $exporter->method('getDomains')->willReturn([]);

        $commandTester = $this->createCommandTester($exporter);
        $commandTester->execute([]);

        $this->assertStringContainsString('No translation found in the database.', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testExportsToNewFile(): void
    {
        $exporter = $this->mockExporter(
            [['id' => 1, 'name' => 'messages', 'locale' => 'en', 'path' => null]],
            ['key_b' => 'Value B', 'key_a' => 'Value A']
        );

        $commandTester = $this->createCommandTester($exporter);
        $commandTester->execute([]);

        $file = self::OUTPUT_DIR.'/translations/messages.en.yaml';
        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, Yaml::dump(['key_a' => 'Value A', 'key_b' => 'Value B']));
        $this->assertStringContainsString('Old: 0', $commandTester->getDisplay());
        $this->assertStringContainsString('New: 2', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testMergesWithExistingFileAndKeepsLocalOnlyKeys(): void
    {
        $this->createYamlFile('translations/messages.en.yaml', [
            'existing_key' => 'Existing Value',
            'shared_key' => 'Old Value',
        ]);

        $exporter = $this->mockExporter(
            [['id' => 1, 'name' => 'messages', 'locale' => 'en', 'path' => null]],
            ['shared_key' => 'New Value', 'new_key' => 'New Key Value']
        );

        $commandTester = $this->createCommandTester($exporter);
        $commandTester->execute([]);

        $file = self::OUTPUT_DIR.'/translations/messages.en.yaml';
        $this->assertStringEqualsFile($file, Yaml::dump([
            'existing_key' => 'Existing Value',
            'new_key' => 'New Key Value',
            'shared_key' => 'New Value',
        ]));
        $this->assertStringContainsString('Old: 2', $commandTester->getDisplay());
        $this->assertStringContainsString('New: 2', $commandTester->getDisplay());
    }

    public function testDbValueOverwritesLocalValue(): void
    {
        $this->createYamlFile('translations/messages.en.yaml', [
            'key1' => 'Local Value',
        ]);

        $exporter = $this->mockExporter(
            [['id' => 1, 'name' => 'messages', 'locale' => 'en', 'path' => null]],
            ['key1' => 'DB Value']
        );

        $commandTester = $this->createCommandTester($exporter);
        $commandTester->execute([]);

        $file = self::OUTPUT_DIR.'/translations/messages.en.yaml';
        $this->assertStringEqualsFile($file, Yaml::dump(['key1' => 'DB Value']));
    }

    public function testOutputIsSortedAlphabetically(): void
    {
        $exporter = $this->mockExporter(
            [['id' => 1, 'name' => 'messages', 'locale' => 'en', 'path' => null]],
            ['zebra' => 'Z', 'apple' => 'A', 'mango' => 'M']
        );

        $commandTester = $this->createCommandTester($exporter);
        $commandTester->execute([]);

        $file = self::OUTPUT_DIR.'/translations/messages.en.yaml';
        $content = file_get_contents($file);
        $this->assertSame("apple: A\nmango: M\nzebra: Z\n", $content);
    }

    public function testCleanDeletesExistingFilesBeforeExport(): void
    {
        $this->createYamlFile('translations/old-domain.en.yaml', ['old_key' => 'Old']);
        $this->createYamlFile('translations/old-domain.hu.yaml', ['old_key' => 'Régi']);

        $exporter = $this->mockExporter(
            [['id' => 1, 'name' => 'messages', 'locale' => 'en', 'path' => null]],
            ['key1' => 'Value1']
        );

        $commandTester = $this->createCommandTester($exporter);
        $commandTester->execute(['--clean' => true]);

        $this->assertFileDoesNotExist(self::OUTPUT_DIR.'/translations/old-domain.en.yaml');
        $this->assertFileDoesNotExist(self::OUTPUT_DIR.'/translations/old-domain.hu.yaml');
        $this->assertFileExists(self::OUTPUT_DIR.'/translations/messages.en.yaml');
        $this->assertStringContainsString('Deleted 2 YAML file(s)', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCleanWithNonExistentDirDoesNotFail(): void
    {
        $exporter = $this->mockExporter(
            [['id' => 1, 'name' => 'messages', 'locale' => 'en', 'path' => null]],
            ['key1' => 'Value1']
        );

        $commandTester = $this->createCommandTester($exporter);
        $commandTester->execute(['--clean' => true]);

        $this->assertFileExists(self::OUTPUT_DIR.'/translations/messages.en.yaml');
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExportsToCustomPath(): void
    {
        $exporter = $this->mockExporter(
            [['id' => 1, 'name' => 'messages', 'locale' => 'en', 'path' => 'custom/messages.en.yaml']],
            ['key1' => 'Value1']
        );

        $commandTester = $this->createCommandTester($exporter);
        $commandTester->execute([]);

        $this->assertFileExists(self::OUTPUT_DIR.'/custom/messages.en.yaml');
    }

    private function createCommandTester(TranslationExporter $exporter): CommandTester
    {
        return new CommandTester(new ExportTranslationsCommand($exporter, new Filesystem(), self::OUTPUT_DIR));
    }

    private function mockExporter(array $domains, array $translations): TranslationExporter
    {
        $exporter = $this->createMock(TranslationExporter::class);
        $exporter->method('getDomains')->willReturn($domains);
        $exporter->method('exportDomain')->willReturn($translations);

        return $exporter;
    }

    private function createYamlFile(string $relativePath, array $data): void
    {
        $path = self::OUTPUT_DIR.'/'.$relativePath;
        $this->filesystem->mkdir(\dirname($path));
        file_put_contents($path, Yaml::dump($data));
    }
}
