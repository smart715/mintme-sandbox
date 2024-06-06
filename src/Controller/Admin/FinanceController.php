<?php declare(strict_types = 1);

namespace App\Controller\Admin;

use App\Controller\Controller;
use App\Manager\CryptoManagerInterface;
use App\Manager\Finance\FinanceBalanceManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/admin-r8bn/finance")
 * @Security(expression="is_granted('ROLE_FINANCIER')")
 * @codeCoverageIgnore
 */
class FinanceController extends Controller
{

    private FinanceBalanceManagerInterface $financeBalanceManager;
    private CryptoManagerInterface $cryptoManager;

    public function __construct(
        NormalizerInterface $normalizer,
        FinanceBalanceManagerInterface $financeBalanceManager,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->financeBalanceManager = $financeBalanceManager;
        $this->cryptoManager = $cryptoManager;

        parent::__construct($normalizer);
    }

    /**
     * @Route("/balance", name="show_balance")
     */
    public function checkBalance(Request $request): Response
    {
        $crypto = $this->cryptoManager->findBySymbol((string)$request->get('crypto'));

        $balanceModels = $this->financeBalanceManager->getBalance($crypto);

        return $this->render(
            'admin/financier/balance.html.twig',
            [
                'balances' => $balanceModels,
            ]
        );
    }

    /**
     * @Route("/income", name="show_income")
     */
    public function checkIncome(Request $request): Response
    {
        $startParam = \DateTimeImmutable::createFromFormat('d-m-Y', (string)$request->get('start_date'));
        $endParam = \DateTimeImmutable::createFromFormat('d-m-Y', (string)$request->get('end_date'));

        $startDate = $startParam ?: (new \DateTimeImmutable())->sub(new \DateInterval('P1M'));
        $endDate = $endParam ?: new \DateTimeImmutable();

        $data = $this->financeBalanceManager->getIncome($startDate, $endDate);

        return $this->render(
            'admin/financier/income.html.twig',
            [
                'items' => $data,
                'startDate' => $startDate->format('d-m-Y'),
                'endDate' => $endDate->format('d-m-Y'),
            ]
        );
    }
}
