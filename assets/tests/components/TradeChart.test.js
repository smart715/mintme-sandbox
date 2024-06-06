import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradeChart from '../../js/components/trade/TradeChart';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => false};
            Vue.prototype.$logger = {error: jest.fn()};
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}
const market = {
    hiddenName: 'TOK000000000001WEB',
    tokenName: 'tok1',
    quote: {
        symbol: 'testQuoteSymbol',
        subunit: 4,
        priceDecimals: 8,
        image: {
            url: require('../../img/default_token_avatar.svg'),
        },
    },
    base: {
        symbol: 'testBaseSymbol',
        subunit: 4,
        image: {
            url: require('../../img/BTC.svg'),
        },
    },
};

/**
 * @param {Object} options
 * @return {Wrapper<Vue>}
 */
function createWrapper(options = {}) {
    const localVue = mockVue();
    localVue.use(Vuex);
    const store = new Vuex.Store({
        modules: {
            websocket: {
                namespaced: true,
                actions: {
                    addOnOpenHandler: jest.fn(),
                    addMessageHandler: jest.fn(),
                },
            },
        },
    });

    return shallowMount(TradeChart, {
        localVue,
        store,
        propsData: {
            market,
            websocketUrl: 'testWebsocketUrl',
            mintmeSupplyUrl: 'testMintmeSupplyUrl',
            minimumVolumeForMarketCap: 0,
            buyDepth: '0',
            isToken: true,
        },
        ...options,
    });
}

describe('TradeChart', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('priceSubunits', () => {
        it('should return priceDecimals', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.priceSubunits).toBe(8);
        });

        it('should return subunit', () => {
            const wrapper = createWrapper({
                propsData: {
                    market: {
                        ...market,
                        quote: {
                            ...market.quote,
                            priceDecimals: null,
                        },
                    },
                },
            });

            expect(wrapper.vm.priceSubunits).toBe(4);
        });
    });

    describe('chartInfoClass', () => {
        it('should return chart-info class', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.chartInfoClass).toBe(
                `card px-3 py-2 my-2 font-weight-semibold text-center col-lg-auto col-sm-5`
            );
        });
    });

    describe('translationsContext', () => {
        it('should return the correct translations context', () => {
            const wrapper = createWrapper();

            const translationsContext = wrapper.vm.translationsContext;

            expect(translationsContext.quoteSymbol).toBe('testQuoteSymbol');
            expect(translationsContext.baseSymbol).toBe('testBaseSymbol');
            expect(translationsContext.baseAvatarDark).toContain('<span class="coin-avatar">');
            expect(translationsContext.quoteBlock).toContain('<span class="coin-avatar">');
            expect(translationsContext.quoteAvatarDark).toContain('<span class="coin-avatar">');
            expect(translationsContext.mintmeBlock).toContain('coin-avatar-mintme');
        });
    });

    describe('isLoading', () => {
        it('should return correct values', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({marketLoaded: true});
            await wrapper.setProps({ordersLoaded: false});
            expect(wrapper.vm.isLoading).toBe(true);

            await wrapper.setData({marketLoaded: false});
            await wrapper.setProps({ordersLoaded: true});
            expect(wrapper.vm.isLoading).toBe(true);

            await wrapper.setData({marketLoaded: false});
            await wrapper.setProps({ordersLoaded: false});
            expect(wrapper.vm.isLoading).toBe(true);

            await wrapper.setData({marketLoaded: true});
            await wrapper.setProps({ordersLoaded: true});
            expect(wrapper.vm.isLoading).toBe(false);
        });
    });

    describe('chartRows', () => {
        it('should return the correct chart rows when stats is not empty', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                stats: [
                    {time: 1638800000, open: 100, close: 120, highest: 130, lowest: 90, volume: 500},
                ],
            });

            const chartRows = wrapper.vm.chartRows;

            expect(chartRows).toHaveLength(1);

            expect(chartRows[0]).toEqual(['2021-12-06', '100', '120', '130', '90', '500']);
        });

        it('should return default chart row when stats is empty', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({stats: []});

            const chartRows = wrapper.vm.chartRows;

            expect(chartRows).toEqual([
                [new Date().toISOString().slice(0, 10), 0, 0, 0, 0, 0],
            ]);
        });
    });

    describe('chartData', () => {
        it('should return the correct chart data', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                stats: [
                    {time: 1638800000, open: 100, close: 120, highest: 130, lowest: 90, volume: 500},
                ],
            });

            const chartData = wrapper.vm.chartData;

            expect(chartData).toEqual({
                columns: [
                    'trade.chart.date',
                    'trade.chart.open',
                    'trade.chart.close',
                    'trade.chart.highest',
                    'trade.chart.lowest',
                    'trade.chart.vol',
                ],
                rows: [
                    ['2021-12-06', '100', '120', '130', '90', '500'],
                ],
            });
        });
    });

    describe('marketCapInfo', () => {
        it('should return the correct market cap information', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isToken: true});
            expect(wrapper.vm.marketCapInfo).toBe('trade.chart.market_cap.info.token');

            await wrapper.setProps({isToken: false});
            expect(wrapper.vm.marketCapInfo).toBe('trade.chart.market_cap.info.mintme');
        });
    });

    describe('buyDepthGuide', () => {
        it('should return the correct buy depth guide for token', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isToken: true});
            expect(wrapper.vm.buyDepthGuide).toBe('trade.chart.buy_depth_guide_body');

            await wrapper.setProps({isToken: false});
            expect(wrapper.vm.buyDepthGuide).toBe('trading.coin.buy_depth.help');
        });
    });

    describe('toMoneyWithTrailingZeroes', () => {
        it('should format the value with trailing zeroes correctly', () => {
            const wrapper = createWrapper();

            const mockValue = 123.456789;
            const formattedValue = wrapper.vm.toMoneyWithTrailingZeroes(mockValue);

            expect(formattedValue).toBe('123.45678900');
        });
    });

    describe('updateMarketKLine', () => {
        it('should update stats on successfull response', async (done) => {
            const wrapper = createWrapper();
            const sendMessage = jest.spyOn(wrapper.vm, 'sendMessage');

            moxios.stubRequest('market_kline', {
                status: 200,
                response: {
                    data: {
                        kline: 'testKline',
                    },
                },
            });

            await wrapper.vm.updateMarketKLine();

            expect(wrapper.vm.lastSymbol).toBe('testBaseSymbol');
            expect(wrapper.vm.marketLoaded).toBe(false);

            moxios.wait(() => {
                expect(wrapper.vm.stats.data.kline).toEqual('testKline');
                expect(sendMessage).toHaveBeenCalled();
                done();
            });
        });

        it('should handle error response', async (done) => {
            const wrapper = createWrapper();

            moxios.stubRequest('market_kline', {
                status: 500,
            });

            await wrapper.vm.updateMarketKLine();

            expect(wrapper.vm.lastSymbol).toBe('testBaseSymbol');
            expect(wrapper.vm.marketLoaded).toBe(false);

            moxios.wait(() => {
                expect(wrapper.vm.stats).toEqual([]);
                expect(wrapper.vm.serviceUnavailable).toBe(true);
                done();
            });
        });
    });

    describe('updateMarketData', () => {
        it('should update market data correctly', () => {
            const wrapper = createWrapper();
            const sendMessage = jest.spyOn(wrapper.vm, 'sendMessage');

            const marketData = {
                params: [
                    {
                        open: '100',
                        last: '120',
                        volume: '150',
                        volumeDonation: '5',
                        deal: '200',
                        dealDonation: '8',
                    },
                    {
                        open: '100',
                        last: '120',
                        volume: '150',
                        volumeDonation: '5',
                        deal: '200',
                        dealDonation: '8',
                    },
                ],
            };

            wrapper.vm.updateMarketData(marketData);

            expect(wrapper.vm.marketStatus).toEqual({
                amount: '208',
                change: '20',
                last: '120',
                marketCap: '0',
                monthAmount: '0',
                monthChange: '0',
                monthVolume: '0',
                volume: '155',
            });
            expect(sendMessage).toHaveBeenCalled();
        });
    });

    describe('updateMonthMarketData', () => {
        it('should update month market data correctly for token markets', async (done) => {
            const wrapper = createWrapper();

            await wrapper.setData({
                marketStatus: {
                    last: '123.45',
                },
            });

            await wrapper.setProps({
                isCreatedOnMintmeSite: true,
                isToken: true,
                minimumVolumeForMarketcap: 100,
            });

            moxios.stubRequest('token_sold_on_market', {
                status: 200,
                response: '5303.60500000',
            });

            wrapper.vm.updateMonthMarketData({
                open: '100',
                last: '150',
                volume: '50',
                volumeDonation: '5',
                deal: '200',
                dealDonation: '10',
            });

            moxios.wait(() => {
                expect(wrapper.vm.marketStatus.monthChange).toBe('50');
                expect(wrapper.vm.marketStatus.monthVolume).toBe('55');
                expect(wrapper.vm.marketStatus.monthAmount).toBe('210');
                expect(wrapper.vm.marketStatus.marketCap).toBe('654730.03724999');
                done();
            });
        });

        it('should handle error response for token', async (done) => {
            const wrapper = createWrapper();

            await wrapper.setData({
                marketStatus: {
                    last: '123.45',
                },
            });

            await wrapper.setProps({
                isCreatedOnMintmeSite: true,
                isToken: true,
                minimumVolumeForMarketcap: 100,
            });

            moxios.stubRequest('token_sold_on_market', {
                status: 500,
            });

            wrapper.vm.updateMonthMarketData({
                open: '100',
                last: '150',
                volume: '50',
                volumeDonation: '5',
                deal: '200',
                dealDonation: '10',
            });

            moxios.wait(() => {
                expect(wrapper.vm.marketStatus.monthChange).toBe('50');
                expect(wrapper.vm.marketStatus.monthVolume).toBe('55');
                expect(wrapper.vm.marketStatus.monthAmount).toBe('210');
                expect(wrapper.vm.marketStatus.marketCap).toBe('-');
                done();
            });
        });

        it('should notify error for non-token markets', async () => {
            const wrapper = createWrapper();
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            await wrapper.setData({
                marketStatus: {
                    last: '123.45',
                },
            });

            await wrapper.setProps({
                isToken: false,
            });

            wrapper.vm.updateMonthMarketData({
                open: '100',
                last: '150',
                volume: '50',
                volumeDonation: '5',
                deal: '200',
                dealDonation: '10',
            });

            expect(notifyErrorSpy).toHaveBeenCalled();
            expect(wrapper.vm.marketStatus.marketCap).toBe('-');
        });

        it('should set marketCap correctly for non-token markets', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                supply: 1000,
                marketStatus: {
                    last: '123.45',
                },
            });

            await wrapper.setProps({
                isToken: false,
            });

            wrapper.vm.updateMonthMarketData({
                open: '100',
                last: '150',
                volume: '50',
                volumeDonation: '5',
                deal: '200',
                dealDonation: '10',
            });

            expect(wrapper.vm.marketStatus.marketCap).toBe('123450');
        });
    });

    describe('getDate', () => {
        it('should format timestamp to YYYY-MM-DD format', () => {
            const wrapper = createWrapper();
            const timestamp = 1637616000;
            const result = wrapper.vm.getDate(timestamp);

            expect(result).toBe('2021-11-22');
        });
    });

    describe('getStartTradingPeriod', () => {
        it('should return the correct start trading period', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                stats: [
                    {time: 1637458800, open: 50000, close: 51000, highest: 52000, lowest: 49000, volume: 100},
                    {time: 1637545200, open: 51000, close: 50500, highest: 51500, lowest: 50000, volume: 90},
                ],
                maxAvailableDays: 1,
            });

            expect(wrapper.vm.getStartTradingPeriod()).toBe(50);
        });

        it('should return 0 if stats length is not greater than max available days', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                stats: [
                    {time: 1637458800, open: 50000, close: 51000, highest: 52000, lowest: 49000, volume: 100},
                ],
                maxAvailableDays: 2,
            });

            expect(wrapper.vm.getStartTradingPeriod()).toBe(0);
        });
    });

    describe('handleRightLabel', () => {
        it('should set axisLabel.show to false for breakpoints doesn\'t return lg or xl', () => {
            const wrapper = createWrapper();

            wrapper.vm.handleRightLabel();

            expect(wrapper.vm.additionalAttributes.yAxis[1].axisLabel.show).toBe(false);
        });
    });

    describe('fetchWEBsupply', () => {
        it('should update the supply on successful request', async (done) => {
            const wrapper = createWrapper({
                propsData: {
                    market,
                    mintmeSupplyUrl: 'test-mintme-supply-url',
                    isToken: true,
                },
            });

            moxios.stubRequest('test-mintme-supply-url', {
                status: 200,
                response: '50000',
            });

            await wrapper.vm.fetchWEBsupply();

            moxios.wait(() => {
                expect(wrapper.vm.supply).toBe(50000);
                done();
            });
        });

        it('should update the supply on successful request', async (done) => {
            const wrapper = createWrapper({
                propsData: {
                    market,
                    mintmeSupplyUrl: 'test-mintme-supply-url',
                    isToken: true,
                },
            });

            moxios.stubRequest('test-mintme-supply-url', {
                status: 500,
                response: '50000',
            });

            moxios.wait(() => {
                expect(wrapper.vm.fetchWEBsupply()).rejects.toThrowError();
                done();
            });
        });
    });

    describe('fetchCirculatingSupply', () => {
        it('should update supply on successful request', async (done) => {
            const wrapper = createWrapper();

            moxios.stubRequest(/markets_circulating_supply/, {
                status: 200,
                response: {
                    circulatingSupply: 5000000,
                },
            });

            await wrapper.vm.fetchCirculatingSupply();

            moxios.wait(() => {
                expect(wrapper.vm.supply).toBe(5000000);
                done();
            });
        });

        it('should set supply to 0 on error', async (done) => {
            const wrapper = createWrapper();

            moxios.stubRequest(/markets_circulating_supply/, {
                status: 500,
            });

            await wrapper.vm.fetchCirculatingSupply();

            moxios.wait(() => {
                expect(wrapper.vm.supply).toBe(0);
                done();
            });
        });
    });

    describe('messageHandler', () => {
        it('should update market data for state update', () => {
            const wrapper = createWrapper();
            const updateMarketData = jest.spyOn(wrapper.vm, 'updateMarketData');
            const updateMonthMarketData = jest.spyOn(wrapper.vm, 'updateMonthMarketData');

            const result = {
                method: 'state.update',
            };

            wrapper.vm.messageHandler(result);

            expect(updateMarketData).toHaveBeenCalledWith(result);
            expect(updateMonthMarketData).not.toHaveBeenCalled();
        });

        it('should update kline data for kline update with matching symbol', async () => {
            const wrapper = createWrapper();
            const result = {
                method: 'kline.update',
                params: [[
                    '2023-01-02T00:00:00.000Z',
                    110,
                    130,
                    140,
                    95,
                    1200,
                ]],
            };

            await wrapper.setData({lastSymbol: 'testBaseSymbol'});

            wrapper.vm.messageHandler(result);

            expect(wrapper.vm.stats[0]).toEqual({
                time: '2023-01-02T00:00:00.000Z',
                open: 110,
                close: 130,
                highest: 140,
                lowest: 95,
                volume: 1200,
            });
        });

        it('should update month market data for matching result id', async () => {
            const wrapper = createWrapper();
            const updateMonthMarketData = jest.spyOn(wrapper.vm, 'updateMonthMarketData');
            const result = {
                id: 123,
                result: {
                    totalVolume: 50000,
                    totalOrders: 200,
                },
            };

            await wrapper.setData({monthInfoRequestId: 123});

            wrapper.vm.messageHandler(result);

            expect(updateMonthMarketData).toHaveBeenCalled();
        });
    });

    describe('getPriceAbbreviation', () => {
        it('should return formatted and abbreviated price when priceSubunits > GENERAL.precision', () => {
            const wrapper = createWrapper({
                propsData: {
                    market: {
                        ...market,
                        quote: {
                            ...market.quote,
                            priceDecimals: 10,
                        },
                    },
                    isToken: true,
                },
            });

            const val = 123.4567890123;

            const result = wrapper.vm.getPriceAbbreviation(val);

            expect(result).toEqual('123.4...0123');
        });

        it('should return unabbreviated price when priceSubunits <= GENERAL.precision', () => {
            const wrapper = createWrapper({
                propsData: {
                    market: {
                        ...market,
                        quote: {
                            ...market.quote,
                            priceDecimals: 6,
                        },
                    },
                    isToken: true,
                },
            });

            const val = 123.4567890123;

            const result = wrapper.vm.getPriceAbbreviation(val);

            expect(result).toEqual('123.456789');
        });
    });
});
