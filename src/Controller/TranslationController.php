<?php

namespace Lordjancso\TranslationBundle\Controller;

use Lordjancso\TranslationBundle\Service\TranslationStats;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class TranslationController
{
    public function __construct(
        private readonly TranslationStats $stats,
        private readonly Environment $twig,
        private readonly array $managedLocales,
    ) {
    }

    public function index(): Response
    {
        return new Response($this->twig->render('@LordjancsoTranslation/Translation/index.html.twig', [
            'managedLocales' => $this->managedLocales,
            'stats' => $this->stats->getStats(),
        ]));
    }
}
