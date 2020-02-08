<?php

namespace Lordjancso\TranslationBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Lordjancso\TranslationBundle\Entity\TranslationDomain;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TranslationController extends AbstractController
{
    protected $em;
    protected $managedLocales;

    public function __construct(EntityManagerInterface $em, array $managedLocales)
    {
        $this->em = $em;
        $this->managedLocales = $managedLocales;
    }

    public function index()
    {
        /** @var TranslationDomain[] $translationDomains */
        $translationDomains = $this->em->getRepository(TranslationDomain::class)->createQueryBuilder('td')
            ->orderBy('td.name', 'ASC')
            ->groupBy('td.name')
            ->getQuery()
            ->getResult();

        return $this->render('@LordjancsoTranslation/Translation/index.html.twig', [
            'managedLocales' => $this->managedLocales,
            'translationDomains' => $translationDomains,
        ]);
    }
}
