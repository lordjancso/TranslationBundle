<?php

namespace Lordjancso\TranslationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Lordjancso\TranslationBundle\Entity\TranslationValue;
use Lordjancso\TranslationBundle\Service\TranslationImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ImportTranslationsCommand extends Command
{
    protected static $defaultName = 'lordjancso:import-translations';

    protected $importer;
    protected $em;
    protected $projectDir;
    protected $managedLocales;

    public function __construct(TranslationImporter $importer, EntityManagerInterface $em, string $projectDir, array $managedLocales)
    {
        $this->importer = $importer;
        $this->em = $em;
        $this->projectDir = $projectDir;
        $this->managedLocales = $managedLocales;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Imports the translations from files to database.')
            ->addOption('import-path', 'p', InputOption::VALUE_OPTIONAL, 'The location of the translation files.', '/translations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('mysql' !== $this->em->getConnection()->getDatabasePlatform()->getName()) {
            $io->error('The import command can only be executed safely on \'mysql\'.');

            return 1;
        }

        $importPath = $this->projectDir.$input->getOption('import-path');

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

            if (!in_array($locale, $this->managedLocales, true)) {
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

        $updateTranslationValues = [];

        foreach ($yaml as $keyName => $content) {
            $content = addslashes($content);
            $translationKeyId = array_search($keyName, $dbTranslationKeys);
            unset($dbTranslationKeys[$translationKeyId]);

            $updateTranslationValues[] = ["'{$content}'", "'{$locale}'", $translationDomain->getId(), $translationKeyId];
        }

        if (!empty($updateTranslationValues)) {
            foreach ($updateTranslationValues as $i => $value) {
                $updateTranslationValues[$i] = implode(',', $value);
            }

            $sql = 'INSERT INTO lj_translation_values (content, locale, domain_id, key_id) VALUES (';
            $sql .= implode('),(', $updateTranslationValues).') ';
            $sql .= 'ON DUPLICATE KEY UPDATE content = VALUES(content)';

            $this->em->getConnection()->executeQuery($sql);
        }

        if (!empty($dbTranslationKeys)) {
            $this->em->getRepository(TranslationValue::class)->deleteAllByDomainAndKey($translationDomain, $dbTranslationKeys);
        }

        // delete translation keys without translation value

        $sql = 'DELETE FROM lj_translation_keys tk WHERE NOT EXISTS (SELECT id FROM lj_translation_values tv WHERE tv.key_id = tk.id)';
        $this->em->getConnection()->executeQuery($sql);

        return [
            'status' => 'success',
            'modify' => count($yaml),
            'delete' => count($dbTranslationKeys),
        ];
    }
}
