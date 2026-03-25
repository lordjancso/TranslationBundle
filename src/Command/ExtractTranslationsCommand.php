<?php

namespace Lordjancso\TranslationBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'lordjancso:extract-translations',
    description: 'Extracts translation keys from source code into YAML files.'
)]
class ExtractTranslationsCommand extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $projectDir,
        private readonly string $translationsDir,
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
            ->addOption('exclude-domain', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Translation domains to exclude.', $this->excludeDomains)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Step 1: Run Symfony's translation:extract command
        $io->section('Extracting translations using Symfony extractor');

        $result = $this->runSymfonyExtract($input, $output);

        if (Command::SUCCESS !== $result) {
            $io->error('Symfony translation:extract failed.');

            return Command::FAILURE;
        }

        // Step 2: Remove excluded domain files
        $excludeDomains = $input->getOption('exclude-domain');

        if (!empty($excludeDomains)) {
            $this->removeExcludedDomains($this->translationsDir, $excludeDomains, $io);
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

    private function removeExcludedDomains(string $translationsDir, array $excludeDomains, SymfonyStyle $io): void
    {
        $absoluteDir = $this->projectDir.'/'.$translationsDir;

        if (!$this->filesystem->exists($absoluteDir)) {
            return;
        }

        $count = 0;

        foreach ($excludeDomains as $domain) {
            $finder = new Finder();
            $finder->files()->in($absoluteDir)->name('/^'.preg_quote($domain, '/').'[.+]/');

            foreach ($finder as $file) {
                $this->filesystem->remove($file->getRealPath());
                ++$count;
            }
        }

        if ($count > 0) {
            $io->comment(sprintf('Removed %d file(s) for excluded domains: %s', $count, implode(', ', $excludeDomains)));
        }
    }
}
