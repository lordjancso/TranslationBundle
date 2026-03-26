<?php

namespace Lordjancso\TranslationBundle\Command;

use Lordjancso\TranslationBundle\Service\TranslationFileManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'lordjancso:extract-translations',
    description: 'Extracts translation keys from source code into YAML files.'
)]
class ExtractTranslationsCommand extends Command
{
    public function __construct(
        private readonly TranslationFileManager $fileManager,
        private readonly array $excludeDomains,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // Symfony translation:extract arguments
            ->addArgument('locale', InputArgument::REQUIRED, 'The locale.')
            ->addArgument('bundle', InputArgument::OPTIONAL, 'The bundle name or directory where to load the messages.')

            // Symfony translation:extract options (with our defaults)
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Override the default prefix.', '')
            ->addOption('no-fill', null, InputOption::VALUE_NONE, 'Extract translation keys without filling in values.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Override the default output format.', 'yaml')
            ->addOption('dump-messages', null, InputOption::VALUE_NONE, 'Should the messages be dumped in the console.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Should the extract be done.')
            ->addOption('clean', null, InputOption::VALUE_NONE, 'Should clean not found messages.')
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, 'Specify the domain to extract.')
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, 'Return list of messages sorted alphabetically.')
            ->addOption('as-tree', null, InputOption::VALUE_REQUIRED, 'Dump the messages as a tree-like structure.')

            // Our own options
            ->addOption('cleanup-locales', null, InputOption::VALUE_NONE, 'Remove keys from other locale files that are not in the given locale. Exits after syncing.')
            ->addOption('reset-files', null, InputOption::VALUE_NONE, 'Delete all existing YAML translation files before extracting.')
            ->addOption('keep-intl-icu-suffix', null, InputOption::VALUE_NONE, 'Keep the +intl-icu suffix on generated files.')
            ->addOption('exclude-domain', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Translation domains to exclude.', $this->excludeDomains)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Sync locales mode: prune other locale files based on reference locale, then exit
        if ($input->getOption('cleanup-locales')) {
            $result = $this->fileManager->cleanupLocales($input->getArgument('locale'));

            if ($result['pruned'] > 0) {
                $io->comment(sprintf('Pruned %d key(s) from %d file(s).', $result['pruned'], $result['files']));
            } else {
                $io->comment('All locale files are in sync.');
            }

            return Command::SUCCESS;
        }

        // Step 1: Reset translation files if requested
        if ($input->getOption('reset-files')) {
            $count = $this->fileManager->resetFiles();

            if ($count > 0) {
                $io->comment(sprintf('Deleted %d YAML file(s).', $count));
            }
        }

        // Step 2: Run Symfony's translation:extract command
        $io->section('Extracting translations using Symfony extractor');

        $result = $this->runSymfonyExtract($input, $output);

        if (Command::SUCCESS !== $result) {
            $io->error('Symfony translation:extract failed.');

            return Command::FAILURE;
        }

        // Step 3: Remove intl-icu suffix from filenames
        if (!$input->getOption('keep-intl-icu-suffix')) {
            $count = $this->fileManager->removeIntlIcuSuffix();

            if ($count > 0) {
                $io->comment(sprintf('Renamed %d file(s) to remove +intl-icu suffix.', $count));
            }
        }

        // Step 4: Normalize YAML files to NFC
        $count = $this->fileManager->normalizeFiles();

        if ($count > 0) {
            $io->comment(sprintf('Normalized %d YAML file(s) to NFC unicode.', $count));
        }

        // Step 5: Remove excluded domain files
        $excludeDomains = $input->getOption('exclude-domain');

        if (!empty($excludeDomains)) {
            $count = $this->fileManager->removeExcludedDomains($excludeDomains);

            if ($count > 0) {
                $io->comment(sprintf('Removed %d file(s) for excluded domains: %s', $count, implode(', ', $excludeDomains)));
            }
        }

        $io->success('Translation extraction completed.');

        return Command::SUCCESS;
    }

    private function runSymfonyExtract(InputInterface $input, OutputInterface $output): int
    {
        $command = $this->getApplication()->find('translation:extract');

        $arguments = [
            'locale' => $input->getArgument('locale'),
            '--format' => $input->getOption('format'),
            '--prefix' => $input->getOption('prefix'),
        ];

        // Pass through boolean flags only when explicitly set
        if ($input->getOption('force') || !$input->getOption('dump-messages')) {
            $arguments['--force'] = true;
        }

        if ($input->getOption('dump-messages')) {
            $arguments['--dump-messages'] = true;
        }

        // Default: both --force and --dump-messages when neither is specified
        if (!$input->getOption('force') && !$input->getOption('dump-messages')) {
            $arguments['--force'] = true;
            $arguments['--dump-messages'] = true;
        }

        if ($input->getOption('no-fill')) {
            $arguments['--no-fill'] = true;
        }

        if ($input->getOption('clean')) {
            $arguments['--clean'] = true;
        }

        if (null !== $input->getOption('domain')) {
            $arguments['--domain'] = $input->getOption('domain');
        }

        if (null !== $input->getOption('sort')) {
            $arguments['--sort'] = $input->getOption('sort');
        }

        if (null !== $input->getOption('as-tree')) {
            $arguments['--as-tree'] = $input->getOption('as-tree');
        }

        if (null !== $input->getArgument('bundle')) {
            $arguments['bundle'] = $input->getArgument('bundle');
        }

        return $command->run(new ArrayInput($arguments), $output);
    }

}
