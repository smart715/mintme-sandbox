<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapProfileListener
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager)
    {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
    }

    public function onPrestasitemapPopulate(SitemapPopulateEvent $event): void
    {
        $this->registerProfilesUrls($event->getUrlContainer());
    }
    public function registerProfilesUrls(UrlContainerInterface $urls): void
    {
        $profilesRepository = $this->entityManager->getRepository(Profile::class);

        $profiles = $profilesRepository->findAll();

        foreach ($profiles as $profile) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'profile-view',
                        ['pageUrl' => $profile->getPageUrl()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'profiles'
            );
        }
    }
}
