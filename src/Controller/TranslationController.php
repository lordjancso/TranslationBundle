<?php

namespace Lordjancso\TranslationBundle\Controller;

use Lordjancso\TranslationBundle\Service\TranslationStats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TranslationController extends AbstractController
{
    public function __construct(
        protected TranslationStats $stats,
        protected array $managedLocales,
    ) {
    }

    public function index(): Response
    {
        return $this->render('@LordjancsoTranslation/Translation/index.html.twig', [
            'managedLocales' => $this->managedLocales,
            'stats' => $this->stats->getStats(),
        ]);
    }
}
