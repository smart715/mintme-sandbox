<?php declare(strict_types = 1);

namespace App\Request\ParamConverter;

use App\Exchange\Market;
use App\Exchange\Market\MarketFinderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarketConverter implements ParamConverterInterface
{
    /** @var MarketFinderInterface */
    private $marketFinder;

    public function __construct(MarketFinderInterface $marketFinder)
    {
        $this->marketFinder = $marketFinder;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $market = $this->marketFinder->find(
            (string)$request->get('base'),
            (string)$request->get('quote')
        );

        if ($market) {
            $request->attributes->set($configuration->getName(), $market);

            return true;
        }

        throw new NotFoundHttpException('Market not found');
    }

    /** @codeCoverageIgnore */
    public function supports(ParamConverter $configuration): bool
    {
        return Market::class === $configuration->getClass();
    }
}
