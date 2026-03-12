<?php

namespace Lordjancso\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

class TranslationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $translatorDefinition = $container->getDefinition('translator.default');
        $locales = $container->getParameter('lordjancso_translation.managed_locales');

        $domains = [];
        $translationsDir = $container->getParameter('kernel.project_dir').'/translations';

        if (!is_dir($translationsDir)) {
            return;
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in($translationsDir)
            ->name('*.*.yaml')
            ->sortByName();

        foreach ($finder as $file) {
            [$domain, $locale, $extension] = explode('.', $file->getFilename());

            if (!in_array($domain, $domains)) {
                $domains[] = $domain;
            }
        }

        foreach ($locales as $locale) {
            foreach ($domains as $domain) {
                $translatorDefinition->addMethodCall('addResource', [
                    'ljdb',
                    new Reference('lordjancso_translation.translation.database_loader'),
                    $locale,
                    $domain,
                ]);
            }
        }
    }
}
