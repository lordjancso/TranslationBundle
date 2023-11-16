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
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('import-path', 'p', InputOption::VALUE_OPTIONAL, 'The location of the translation files.', '/translations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->manager->isDatabasePlatformSupported()) {
            $io->error('The import command can only be executed safely on \'mysql\'.');

            return 1;
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

            return 1;
        }

        foreach ($finder as $file) {
            $io->write("Importing {$file->getRealPath()}... ");

            $relativePath = str_replace($this->projectDir.'/', '', $file->getRealPath());
            list($domain, $locale) = explode('.', $file->getFilename());

            if (!in_array($locale, $this->manager->getManagedLocales(), true)) {
                $io->writeln('<comment>SKIP! Not in managed locales.</comment>');

                continue;
            }

            $result = $this->importTranslationDomain($domain, $locale, $relativePath);

            if ('no_changes' === $result['status']) {
                $io->writeln('<comment>SKIP! No changes in the file.</comment>');
            } elseif ('success' === $result['status']) {
                $io->writeln("<info>SUCCESS! {$result['modify']} modified, {$result['delete']} deleted.</info>");
            }
        }

        // TODO
        // delete empty translation chains

        return 0;
    }

    protected function importTranslationDomain(string $domain, string $locale, string $relativePath): array
    {
        // manage TranslationDomain

        $translationDomain = $this->importer->importDomain($domain, $locale, $relativePath);

        if (!$translationDomain instanceof TranslationDomain) {
            return [
                'status' => 'no_changes',
            ];
        }

        // manage TranslationKey

        $yaml = Yaml::parseFile($relativePath);
        $dbTranslationKeys = $this->importer->importKeys($translationDomain, $yaml);

        // manage TranslationValue

        $contentsAndKeyIds = [];

        foreach ($yaml as $keyName => $content) {
            $translationKeyId = array_search($keyName, $dbTranslationKeys);
            $contentsAndKeyIds[$translationKeyId] = $content;

            unset($dbTranslationKeys[$translationKeyId]);
        }

        if (!empty($contentsAndKeyIds)) {
            $this->importer->importValues($translationDomain->getId(), $locale, $contentsAndKeyIds);
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
