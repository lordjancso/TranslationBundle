<?php

namespace Lordjancso\TranslationBundle\Command;

use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Service\TranslationImporter;
use Lordjancso\TranslationBundle\Service\TranslationManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'lordjancso:import-translations',
    description: 'Imports the translations from files to database.'
)]
class ImportTranslationsCommand extends Command
{
    public function __construct(
        private readonly TranslationManager $manager,
        private readonly TranslationImporter $importer,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('import-path', 'p', InputOption::VALUE_OPTIONAL, 'The location of the translation files.', '/translations')
            ->addOption('truncate', null, InputOption::VALUE_NONE, 'Truncate all translation tables before importing.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Import without confirmation even if tables are not empty.')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Number of rows per INSERT batch.', 500)
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Sleep between batches in milliseconds.', 0)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->manager->isDatabasePlatformSupported()) {
            $io->error('The import command can only be executed safely on \'mysql\'.');

            return Command::FAILURE;
        }

        if ($input->getOption('truncate')) {
            $this->manager->truncate();
            $io->comment('All translation tables truncated.');
        } elseif (!$this->manager->isTablesEmpty()) {
            if (!$input->getOption('force')) {
                if (!$io->confirm('Translation tables are not empty. Continue importing?', false)) {
                    $io->warning('Import cancelled.');

                    return Command::SUCCESS;
                }
            }
        }

        $importPath = $input->getOption('import-path');

        if ('/' !== substr($importPath, 0, 1)) {
            $importPath = "/{$importPath}";
        }

        $importPath = $this->projectDir.$importPath;

        $finder = new Finder();
        $finder
            ->files()
            ->in($importPath)
            ->name('*.*.yaml')
            ->sortByName();

        if (!$finder->hasResults()) {
            $io->getErrorStyle()->error("No translation file found in path '{$importPath}'.");

            return Command::FAILURE;
        }

        $this->manager->beginTransaction();

        try {
            foreach ($finder as $file) {
                $io->write("Importing {$file->getRealPath()}... ");

                $relativePath = str_replace($this->projectDir.'/', '', $file->getRealPath());
                [$domain, $locale] = explode('.', $file->getFilename());

                if (!in_array($locale, $this->manager->getManagedLocales(), true)) {
                    $io->writeln('<comment>SKIP! Not in managed locales.</comment>');

                    continue;
                }

                $result = $this->importTranslationDomain($domain, $locale, $relativePath, $file->getRealPath(), (int) $input->getOption('batch-size'), (int) $input->getOption('sleep'));

                $sleep = (int) $input->getOption('sleep');

                if ('no_changes' === $result['status']) {
                    $io->writeln('<comment>SKIP! No changes in the file.</comment>');
                } elseif ('success' === $result['status']) {
                    $io->writeln("<info>SUCCESS! {$result['modify']} modified, {$result['delete']} deleted.</info>");

                    if ($sleep > 0) {
                        usleep($sleep * 1000);
                    }
                }
            }

            $this->manager->commit();
        } catch (\Throwable $e) {
            $this->manager->rollBack();

            throw $e;
        }

        return Command::SUCCESS;
    }

    protected function importTranslationDomain(string $domain, string $locale, string $relativePath, string $absolutePath, int $batchSize, int $sleepBetweenBatches): array
    {
        // manage TranslationDomain

        $translationDomain = $this->importer->importDomain($domain, $locale, $relativePath, $absolutePath);

        if (!$translationDomain instanceof TranslationDomain) {
            return [
                'status' => 'no_changes',
            ];
        }

        // manage TranslationKey

        $yaml = Yaml::parseFile($absolutePath);
        $dbTranslationKeys = $this->importer->importKeys($translationDomain, $yaml);

        // manage TranslationValue

        $contentsAndKeyIds = [];

        foreach ($yaml as $keyName => $content) {
            $translationKeyId = array_search($keyName, $dbTranslationKeys);
            $contentsAndKeyIds[$translationKeyId] = $content;

            unset($dbTranslationKeys[$translationKeyId]);
        }

        if (!empty($contentsAndKeyIds)) {
            $this->importer->importValues($translationDomain->getId(), $locale, $contentsAndKeyIds, $batchSize, $sleepBetweenBatches);
        }

        if (!empty($dbTranslationKeys)) {
            $this->importer->deleteAllTranslationValues($translationDomain, $dbTranslationKeys);
        }

        return [
            'status' => 'success',
            'modify' => count($yaml),
            'delete' => count($dbTranslationKeys),
        ];
    }
}
