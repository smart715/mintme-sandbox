<?php declare(strict_types = 1);

namespace App\Command\Crypto;

use App\Entity\WrappedCryptoToken;
use App\Repository\WrappedCryptoTokenRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowCryptoNetworksCommand extends Command
{
    private WrappedCryptoTokenRepository $wctRepository;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(WrappedCryptoTokenRepository $wctRepository, MoneyWrapperInterface $moneyWrapper)
    {
        $this->wctRepository = $wctRepository;
        $this->moneyWrapper = $moneyWrapper;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:show-crypto-networks')
            ->setDescription('Show all crypto networks in a table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);

        $rows = $this->generateRows();

        $table
            ->setHeaders(['Crypto', 'Blockchain', 'Address', 'Fee', 'Status'])
            ->setRows($rows);

        $table->setColumnMaxWidth(2, 100);
        $table->setHeaderTitle('Crypto Networks');
        $table->setFooterTitle(count($rows).' rows');

        $table->render();

        return 0;
    }

    private function generateRows(): array
    {
        $networks = $this->getAllCryptoNetworks();

        return array_map(fn(WrappedCryptoToken $wct) => [
            $wct->getCrypto()->getSymbol(),
            $wct->getCryptoDeploy()->getSymbol(),
            $wct->getAddress(),
            $this->moneyWrapper->format($wct->getFee(), false) . ' ' . $wct->getFeeCurrency(),
            $wct->isEnabled() ? 'Enabled' : 'Disabled',
        ], $networks);
    }

    /**
     * @return WrappedCryptoToken[]
     */
    private function getAllCryptoNetworks(): array
    {
        return $this->wctRepository->findBy([], ['crypto' => Criteria::ASC]);
    }
}
