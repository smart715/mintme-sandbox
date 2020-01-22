<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Exception\ApiBadRequestException;
use App\Logger\FrontEndLogger;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/logs")
 */
class LoggerController extends APIController
{
    /**
     * @Rest\View()
     * @Rest\Post("/",name="log", options={"expose"=true})
     * @Rest\RequestParam(
     *     name="level",
     *     allowBlank=false,
     *     requirements="(info|emergency|alert|critical|error|warning|notice|debug)"
     * )
     * @Rest\RequestParam(name="message", allowBlank=false)
     * @Rest\RequestParam(name="context", allowBlank=true, nullable=true)
     */
    public function log(FrontEndLogger $logger, ParamFetcherInterface $fetcher): View
    {
        $logLevel = $fetcher->get('level');

        if (!method_exists($logger, $logLevel)) {
            throw new ApiBadRequestException('Undefined log level');
        }

        $cutHeaders = $this->getParameter('front_end_logs_cut_headers');
        $message = $fetcher->get('message');
        $context = json_decode($fetcher->get('context') ?? '', true);

        if ($cutHeaders && is_array($context)) {
            $context = $this->filterRequest($context);
        }

        $logger->{$logLevel}($message, is_array($context) ? $context : []);

        return $this->view(Response::HTTP_OK);
    }

    /**
     * @param array|string $arr
     * @param string $key
     * @return array|string
     */
    public function filterRequest($arr, string $key = 'headers')
    {
        if (is_array($arr) && array_key_exists($key, $arr)) {
            unset($arr[$key]);
        }

        return is_iterable($arr)
            ?  array_map([$this, 'filterRequest'], $arr)
            :  $arr;
    }
}
