<?php

namespace Lordjancso\TranslationBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class TranslationFileManager
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $projectDir,
        private readonly string $translationsDir,
    ) {
    }

    public function getAbsoluteTranslationsDir(): string
    {
        return $this->projectDir.'/'.$this->translationsDir;
    }

    public function resetFiles(): int
    {
        $absoluteDir = $this->getAbsoluteTranslationsDir();

        if (!$this->filesystem->exists($absoluteDir)) {
            return 0;
        }

        $finder = new Finder();
        $finder->files()->in($absoluteDir)->name('*.yaml');

        $count = $finder->count();

        if (0 === $count) {
            return 0;
        }

        foreach ($finder as $file) {
            $this->filesystem->remove($file->getRealPath());
        }

        return $count;
    }

    public function removeExcludedDomains(array $excludeDomains): int
    {
        $absoluteDir = $this->getAbsoluteTranslationsDir();

        if (!$this->filesystem->exists($absoluteDir)) {
            return 0;
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

        return $count;
    }

    public function removeIntlIcuSuffix(): int
    {
        $absoluteDir = $this->getAbsoluteTranslationsDir();

        if (!$this->filesystem->exists($absoluteDir)) {
            return 0;
        }

        $finder = new Finder();
        $finder->files()->in($absoluteDir)->name('*+intl-icu.*.yaml');

        $count = 0;

        foreach ($finder as $file) {
            $newName = str_replace('+intl-icu', '', $file->getRealPath());

            if ($this->filesystem->exists($newName)) {
                // Merge intl-icu content into existing file
                $existingContent = \Symfony\Component\Yaml\Yaml::parseFile($newName) ?: [];
                $intlIcuContent = \Symfony\Component\Yaml\Yaml::parseFile($file->getRealPath()) ?: [];
                $merged = array_merge($existingContent, $intlIcuContent);
                ksort($merged);
                $this->filesystem->dumpFile($newName, \Symfony\Component\Yaml\Yaml::dump($merged));
            } else {
                $this->filesystem->rename($file->getRealPath(), $newName);
            }

            $this->filesystem->remove($file->getRealPath());
            ++$count;
        }

        return $count;
    }

    public function cleanupLocales(string $referenceLocale): array
    {
        $absoluteDir = $this->getAbsoluteTranslationsDir();

        if (!$this->filesystem->exists($absoluteDir)) {
            return ['pruned' => 0, 'files' => 0];
        }

        // Find all reference locale files
        $refFinder = new Finder();
        $refFinder->files()->in($absoluteDir)->name('*.'.$referenceLocale.'.yaml');

        $pruned = 0;
        $filesModified = 0;

        foreach ($refFinder as $refFile) {
            $refKeys = array_keys(\Symfony\Component\Yaml\Yaml::parseFile($refFile->getRealPath()) ?: []);
            $domain = explode('.', $refFile->getFilename())[0];

            // Find all other locale files for this domain
            $otherFinder = new Finder();
            $otherFinder->files()->in($absoluteDir)->name('/^'.preg_quote($domain, '/').'\.(?!'.preg_quote($referenceLocale, '/').'\.)\w+\.yaml$/');

            foreach ($otherFinder as $otherFile) {
                $otherYaml = \Symfony\Component\Yaml\Yaml::parseFile($otherFile->getRealPath()) ?: [];
                $filtered = array_intersect_key($otherYaml, array_flip($refKeys));

                $removed = \count($otherYaml) - \count($filtered);

                if ($removed > 0) {
                    ksort($filtered);
                    $this->filesystem->dumpFile($otherFile->getRealPath(), \Symfony\Component\Yaml\Yaml::dump($filtered));
                    $pruned += $removed;
                    ++$filesModified;
                }
            }
        }

        return ['pruned' => $pruned, 'files' => $filesModified];
    }

    public function normalizeFiles(): int
    {
        $absoluteDir = $this->getAbsoluteTranslationsDir();

        if (!$this->filesystem->exists($absoluteDir)) {
            return 0;
        }

        $finder = new Finder();
        $finder->files()->in($absoluteDir)->name('*.yaml');

        $count = 0;

        foreach ($finder as $file) {
            $yaml = \Symfony\Component\Yaml\Yaml::parseFile($file->getRealPath()) ?: [];
            $normalized = [];
            $changed = false;

            foreach ($yaml as $key => $value) {
                $nKey = \Normalizer::normalize((string) $key, \Normalizer::FORM_C);
                $nValue = \Normalizer::normalize((string) $value, \Normalizer::FORM_C);

                if ($nKey !== (string) $key || $nValue !== (string) $value) {
                    $changed = true;
                }

                $normalized[$nKey] = $nValue;
            }

            if ($changed || \count($normalized) !== \count($yaml)) {
                ksort($normalized);
                $this->filesystem->dumpFile($file->getRealPath(), \Symfony\Component\Yaml\Yaml::dump($normalized));
                ++$count;
            }
        }

        return $count;
    }
}
