<?php

namespace Lordjancso\TranslationBundle\Command;

use Lordjancso\TranslationBundle\Service\TranslationExporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'lordjancso:export-translations',
    description: 'Exports the translations from database to files.'
)]
class ExportTranslationsCommand extends Command
{
    public function __construct(
        private readonly TranslationExporter $exporter,
        private readonly Filesystem $filesystem,
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('export-path', 'p', InputOption::VALUE_OPTIONAL, 'The location of the translation files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $exportPath = $this->projectDir.$input->getOption('export-path');
        $translationDomains = $this->exporter->getDomains();

        if (0 === count($translationDomains)) {
            $io->getErrorStyle()->error('No translation found in the database.');

            return Command::FAILURE;
        }

        $this->filesystem->mkdir($exportPath);

        foreach ($translationDomains as $translationDomain) {
            $newTranslations = $this->exporter->exportDomain($translationDomain['id'], $translationDomain['name']);
            $oldTranslations = [];
            $filename = $exportPath.'/'.($translationDomain['path'] ?: 'translations/'.$translationDomain['name'].'.'.$translationDomain['locale'].'.yaml');

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

        return Command::SUCCESS;
    }
}
