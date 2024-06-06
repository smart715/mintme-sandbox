<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Config\CryptoConfig;
use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Entity\Voting\CryptoVoting;
use App\Manager\CryptoManager;
use App\Repository\CryptoRepository;
use App\Repository\CryptoVotingRepository;
use App\Services\TranslatorService\Translator;
use App\Utils\Symbols;
use Doctrine\ORM\AbstractQuery as Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CryptoManagerTest extends TestCase
{
    /**
     * @dataProvider findBySymbolDataProvider
     */
    public function testFindBySymbol(
        bool $isCryptoEnabled,
        string $symbol,
        ?object $expected
    ): void {
        $crypto = $this->mockCrypto();

        $repository = $this->mockCryptoRepository();
        $repository
            ->method('getBySymbol')
            ->willReturn($crypto);

        $manager = new CryptoManager(
            $repository,
            $this->createHideFeaturesConfig($isCryptoEnabled),
            $this->mockCryptoVotingRepository(),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(CryptoConfig::class)
        );

        $this->assertEquals($expected, $manager->findBySymbol($symbol));
    }

    /**
     * @dataProvider findAllDataProvider
     */
    public function testFindAll(
        bool $isCryptoEnabled,
        array $cryptos,
        ?array $expectedKeyValue
    ): void {

        $repository = $this->mockCryptoRepository();
        $repository
            ->method('findAll')
            ->willReturn($cryptos);

        $manager = new CryptoManager(
            $repository,
            $this->createHideFeaturesConfig($isCryptoEnabled),
            $this->mockCryptoVotingRepository(),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(CryptoConfig::class)
        );

        $expected = [];

        if (is_array($expectedKeyValue)) {
            foreach ($expectedKeyValue as $key => $value) {
                $expected[$key] = $cryptos[$value];
            }
        }

        $this->assertEquals($expected, $manager->findAll());
    }

    /**
     * @dataProvider findAllIndexedDataProvider
     */
    public function testFindAllIndexed(
        bool $isArray,
        int $getQueryTimes,
        int $getArrayResultTimes,
        int $getResultTimes,
        array $expected
    ): void {

        $index = '1';

        $query = $this->mockQuery();
        $query
            ->expects($this->exactly($getArrayResultTimes))
            ->method('getArrayResult')
            ->willReturn(['resultArray']);
        $query
            ->expects($this->exactly($getResultTimes))
            ->method('getResult')
            ->willReturn(['result']);

        $queryBuilder = $this->mockQueryBuilder();
        $queryBuilder
            ->method('expr')
            ->willReturn($this->createMock(Expr::class));
        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->willReturn($queryBuilder);
        $queryBuilder
            ->expects($this->exactly($getQueryTimes))
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->mockCryptoRepository();
        $repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c', "c.{$index}")
            ->willReturn($queryBuilder);

        $manager = new CryptoManager(
            $repository,
            $this->createHideFeaturesConfig(true),
            $this->mockCryptoVotingRepository(),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(CryptoConfig::class)
        );

        $this->assertEquals($expected, $manager->findAllIndexed($index, $isArray));
    }

    public function testGetVotingByCryptoId(): void
    {
        $cryptoId = 1;
        $offset = 0;
        $limit = 10;
        $cryptoVoting = $this->mockCryptoVoting();

        $cryptoVotingRepository = $this->mockCryptoVotingRepository();
        $cryptoVotingRepository
            ->method('getVotingByCryptoId')
            ->with($cryptoId, $offset, $limit)
            ->willReturnOnConsecutiveCalls([$cryptoVoting], []);

        $cryptoManager = new CryptoManager(
            $this->mockCryptoRepository(),
            $this->createHideFeaturesConfig(true),
            $cryptoVotingRepository,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(CryptoConfig::class)
        );

        $this->assertEquals([$cryptoVoting], $cryptoManager->getVotingByCryptoId($cryptoId, $offset, $limit));
        $this->assertEmpty($cryptoManager->getVotingByCryptoId($cryptoId, $offset, $limit));
    }

    public function testGetVotingCountAll(): void
    {
        $votingCount = 3;

        $cryptoVotingRepository = $this->mockCryptoVotingRepository();
        $cryptoVotingRepository
            ->method('getVotingCountAll')
            ->willReturn($votingCount);

        $cryptoManager = new CryptoManager(
            $this->mockCryptoRepository(),
            $this->createHideFeaturesConfig(true),
            $cryptoVotingRepository,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(CryptoConfig::class)
        );

        $this->assertEquals($votingCount, $cryptoManager->getVotingCountAll());
    }

    public function findAllIndexedDataProvider(): array
    {
        return [
            [
                'isArray' => true,
                'getQueryTimes' => 1,
                'getArrayResultTimes' => 1,
                'getResultTimes' => 0,
                'expected' => ['resultArray'],
            ],
            [
                'isArray' => false,
                'getQueryTimes' => 1,
                'getArrayResultTimes' => 0,
                'getResultTimes' => 1,
                'expected' => ['result'],
            ],
        ];
    }

    public function findBySymbolDataProvider(): array
    {
        return [
            'Return null if isCryptoEnabled false' => [
                'isCryptoEnabled' => false,
                'symbol' => 'btc',
                'expected' => null,
            ],
            'Return crypto if isCryptoEnabled true' => [
                'isCryptoEnabled' => true,
                'symbol' => 'btc',
                'expected' => $this->mockCrypto(),
            ],
        ];
    }

    public function findAllDataProvider(): array
    {
        return [
            'Return two cryptos if isCryptoEnabled true' => [
                'isCryptoEnabled ' => true,
                'cryptos' => $this->arrayCryptos(['BTC','ETH']),
                'expectedKeyValue' => [0=>0,1=>1],
            ],
            'Return empty array if isCryptoEnabled false' => [
                'hideFeatures' => false,
                'cryptos' => $this->arrayCryptos(['BTC','ETH']),
                'expectedKeyValue' => [],
            ],
        ];
    }

    private function createHideFeaturesConfig(bool $isCryptoEnabled): HideFeaturesConfig
    {
        $hideFeaturesConfig = $this->createMock(HideFeaturesConfig::class);
        $hideFeaturesConfig
            ->method('isCryptoEnabled')
            ->willReturn($isCryptoEnabled);

        return $hideFeaturesConfig;
    }

    private function arrayCryptos(array $symbols): array
    {
        return array_map(function ($symbol) {
            /** @var Crypto|MockObject $crypto */
            $crypto = $this->mockCrypto();

            $crypto
                ->method('getSymbol')
                ->willReturn($symbol);

            return $crypto;
        }, $symbols);
    }

    /** @return MockObject|CryptoRepository */
    private function mockCryptoRepository(): CryptoRepository
    {
        return $this->createMock(CryptoRepository::class);
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    /** @return MockObject|CryptoVotingRepository */
    private function mockCryptoVotingRepository(): CryptoVotingRepository
    {
        return $this->createMock(CryptoVotingRepository::class);
    }

    /** @return MockObject|QueryBuilder */
    private function mockQueryBuilder(): QueryBuilder
    {
        return $this->createMock(QueryBuilder::class);
    }

    /** @return MockObject|Query */
    private function mockQuery(): Query
    {
        return $this->createMock(Query::class);
    }

    private function mockCryptoVoting(): CryptoVoting
    {
        return $this->createMock(CryptoVoting::class);
    }
}
