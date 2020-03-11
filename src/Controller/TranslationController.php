<?php

namespace Lordjancso\TranslationBundle\Controller;

use Lordjancso\TranslationBundle\Service\TranslationStats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TranslationController extends AbstractController
{
    protected $stats;
    protected $managedLocales;

    public function __construct(TranslationStats $stats, array $managedLocales)
    {
        $this->stats = $stats;
        $this->managedLocales = $managedLocales;
    }

    public function index()
    {
        return $this->render('@LordjancsoTranslation/Translation/index.html.twig', [
            'managedLocales' => $this->managedLocales,
            'stats' => $this->stats->getStats(),
        ]);
    }
}
