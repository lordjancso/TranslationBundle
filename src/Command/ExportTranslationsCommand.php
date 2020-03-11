<?php

namespace Lordjancso\TranslationBundle\Command;

use Lordjancso\TranslationBundle\Service\TranslationExporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ExportTranslationsCommand extends Command
{
    protected static $defaultName = 'lordjancso:export-translations';

    protected $exporter;
    protected $filesystem;
    protected $projectDir;

    public function __construct(TranslationExporter $exporter, Filesystem $filesystem, string $projectDir)
    {
        $this->exporter = $exporter;
        $this->filesystem = $filesystem;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Exports the translations from database to files.')
            ->addOption('export-path', 'p', InputOption::VALUE_OPTIONAL, 'The location of the translation files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $exportPath = $this->projectDir.$input->getOption('export-path');
        $translationDomains = $this->exporter->getDomains();

        if (0 === count($translationDomains)) {
            $io->getErrorStyle()->error('No translation found in the database.');

            return 1;
        }

        $this->filesystem->mkdir($exportPath);

        foreach ($translationDomains as $translationDomain) {
            $newTranslations = $this->exporter->exportDomain($translationDomain['id'], $translationDomain['name']);
            $oldTranslations = [];
            $filename = $exportPath.'/'.$translationDomain['path'];

            if (!$this->filesystem->exists(pathinfo($filename)['dirname'])) {
                $this->filesystem->mkdir(pathinfo($filename)['dirname']);
            }

            if ($this->filesystem->exists($filename)) {
                $oldTranslations = Yaml::parseFile($filename) ?: [];
            }

            $translations = array_merge($oldTranslations, $newTranslations);
            ksort($translations);

            if (0 < count($translations)) {
                $yaml = Yaml::dump($translations);
                file_put_contents($filename, $yaml);
            }

            $io->listing([
                $translationDomain['name'].'.'.$translationDomain['locale'],
                'Old: '.count($oldTranslations),
                'New: '.count($newTranslations),
            ]);
        }

        return 0;
    }
}
