import {createLocalVue, shallowMount} from '@vue/test-utils';
import Trading from '../../js/components/trading/Trading';
import moxios from 'moxios';
import axios from 'axios';

// TODO: Improve tests and add more tests

const filterForTokens = {
    deployed_first: 1,
    deployed_only_mintme: 2,
    airdrop_only: 3,
    deployed_only_eth: 4,
};

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val, params) => {
                    return val + Object.entries(params).reduce((acc, param) => acc + `?${param[0]}=${param[1]}`, '');
                }};
            Vue.prototype.$toasted = {show: () => false};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockTrading(props = {}) {
    return shallowMount(Trading, {
        localVue: mockVue(),
        stubs: ['b-table', 'b-pagination', 'font-awesome-icon', 'b-dropdown', 'b-dropdown-item', 'b-link'],
        propsData: {
            websocketUrl: 'testWebsocketUrl',
            enableUsd: true,
            filterForTokens: filterForTokens,
            ...props,
        },
        methods: {
            initialLoad: () => false,
        },
    });
}

describe('Trading', () => {
    beforeEach(() => {
        moxios.install();
    });
    afterEach(() => {
        moxios.uninstall();
    });

    let market = {
        position: undefined,
        pair: 'MobCoin',
        change: '0%',
        lastPrice: '0.5 WEB',
        volume: '0 WEB',
        monthVolume: '0 WEB',
        tokenUrl: '/token/MobCoin',
        lastPriceUSD: '0.0002 USD',
        volumeUSD: '0 USD',
        monthVolumeUSD: '0 USD',
        marketCap: '0 WEB',
        marketCapUSD: '0 USD',
        tokenized: false,
        base: 'WEB',
        quote: 'MobCoin',
    };
    it('show message if there are not deployed tokens yet', () => {
        const wrapper = mockTrading();
        wrapper.vm.marketFilters.selectedFilter = 'deployed';
        wrapper.vm.marketFilters.userSelected = true;
        wrapper.vm.sanitizedMarkets = {};
        wrapper.vm.markets = {};
        wrapper.vm.loading = false;

        expect(wrapper.html().includes('trading.no_one_deployed')).toBe(true);
        wrapper.vm.sanitizedMarkets = market;
        expect(wrapper.html().includes('trading.no_one_deployed')).toBe(false);
    });
    it('show message if user has no any token yet', () => {
        const wrapper = mockTrading();
        wrapper.vm.sanitizedMarkets = {};
        wrapper.vm.marketFilters.selectedFilter = 'user';
        wrapper.vm.markets = {};
        wrapper.vm.loading = false;

        expect(wrapper.html().includes('trading.no_any_token')).toBe(true);
        wrapper.vm.sanitizedMarkets = market;
        expect(wrapper.html().includes('trading.no_any_token')).toBe(false);
    });
    it('show rest of token link', () => {
        const wrapper = mockTrading();
        wrapper.vm.marketFilters.selectedFilter = 'deployed';
        wrapper.vm.sanitizedMarkets = {};
        wrapper.vm.markets = {};
        wrapper.vm.loading = false;
        expect(wrapper.html().includes('trading.show_all_tokens')).toBe(false);
        wrapper.vm.sanitizedMarkets = market;
        expect(wrapper.html().includes('trading.show_all_tokens')).toBe(true);
    });
    it('make sure that expected "user=1" will be sent', (done) => {
        const wrapper = mockTrading();
        wrapper.vm.toggleFilter('user');
        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            expect(request.url).toContain('user=1');
            done();
        });
    });
    it('make sure that expected "filter=2" will be sent', (done) => {
        const wrapper = mockTrading();
        wrapper.vm.toggleFilter('deployed');
        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            expect(request.url).toContain('filter=2');
            done();
        });
    });
    it('make sure that "user=1" or "filter=2" is not will be sent when user selected "all tokens"', (done) => {
        const wrapper = mockTrading();
        wrapper.vm.toggleFilter('all');
        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            expect(request.url).not.toContain('filter=2');
            expect(request.url).not.toContain('user=1');
            done();
        });
    });
    describe('marketCapFormatter() function should return ', () => {
        it('dash(-) if market is for token and monthVolume less than minimumVolumeForMarketcap', () => {
            const wrapper = mockTrading({minimumVolumeForMarketcap: 10});
            let item = {
                base: 'MINTME',
                monthVolume: 9,
            };
            expect(wrapper.vm.marketCapFormatter('9', 0, item)).toBe('-');
        });

        it('value if market is not for token', () => {
            const wrapper = mockTrading({minimumVolumeForMarketcap: 10});
            let item = {
                base: 'foo',
                monthVolume: 10,
            };
            expect(wrapper.vm.marketCapFormatter('10', 0, item)).toBe('10');
        });

        it('value if monthVolume not less than minimumVolumeForMarketcap', () => {
            const wrapper = mockTrading({minimumVolumeForMarketcap: 10});
            let item = {
                base: 'MINTME',
                monthVolume: 10,

            };
            expect(wrapper.vm.marketCapFormatter('9', 0, item)).toBe('9');
        });
    });

    describe('data field', () => {
        describe(':tokens', () => {
            describe('when fetch markets from server', () => {
                const web = {
                    symbol: 'WEB',
                    image: {avatar_small: ''},
                };
                const btc = {
                    symbol: 'BTC',
                    image: {avatar_small: ''},
                };
                const markets = {
                    TOK000000000001WEB: {
                        base: web,
                        quote: {
                            symbol: 'tok1',
                            image: {avatar_small: ''},
                        },
                        monthVolume: '0',
                        buyDepth: '0',
                        supply: '0',
                    }, // ['tok1', 'WEB'],
                    TOK000000000002WEB: {
                        base: web,
                        quote: {
                            symbol: 'tok2',
                            image: {avatar_small: ''},
                        },
                        monthVolume: '0',
                        buyDepth: '0',
                        supply: '0',
                    }, // ['tok2', 'WEB'],
                    TOK000000000003BTC: {
                        base: btc,
                        quote: web,
                        monthVolume: '0',
                        buyDepth: '0',
                        supply: '0',
                    }, // ['WEB', 'BTC'],
                };

                const wrapper = mockTrading({minimumVolumeForMarketcap: Infinity});
                wrapper.vm.markets = Object.assign({}, markets);

                it('should contain WEB/tok1', (done) => {
                    wrapper.vm.sanitizeMarket({
                        method: 'state.update',
                        params: [
                            'TOK000000000001WEB',
                            {
                                period: 86400,
                                last: '123',
                                open: '456',
                                close: '789',
                                high: '0',
                                low: '0',
                                volume: '0',
                                deal: '321',
                            },
                        ],
                        id: null,
                    });

                    wrapper.vm.$nextTick(() => {
                        wrapper.vm.$nextTick(() => {
                            expect(wrapper.vm.tokens).toMatchObject([
                                {pair: 'tok1', change: '-73%', lastPrice: '123 MINTME', dayVolume: '321 MINTME'},
                            ]);
                            done();
                        });
                    });
                });

                it('should contain WEB/BTC in sanitizedMarketsOnTop', (done) => {
                    wrapper.vm.sanitizeMarket({
                        method: 'state.update',
                        params: [
                            'TOK000000000003BTC',
                            {
                                period: 86400,
                                last: '12',
                                open: '45',
                                close: '78',
                                high: '0',
                                low: '0',
                                volume: '0',
                                deal: '32',
                        },
                        ],
                        id: null,
                    });

                    wrapper.vm.$nextTick(() => {
                        wrapper.vm.$nextTick(() => {
                            expect(wrapper.vm.sanitizedMarketsOnTop).toMatchObject([
                                {pair: 'WEB/BTC', change: '-73%', lastPrice: '12 BTC', dayVolume: '32 BTC'},
                            ]);
                            done();
                        });
                    });
                });

                it('should contain tok1 before tok2', (done) => {
                    wrapper.vm.sanitizeMarket({
                        method: 'state.update',
                        params: [
                            'TOK000000000002WEB',
                            {
                                period: 86400,
                                last: '1230',
                                open: '4560',
                                close: '7890',
                                high: '0',
                                low: '0',
                                volume: '0',
                                deal: '3210',
                        },
                        ],
                        id: null,
                    });

                    wrapper.vm.$nextTick(() => {
                        wrapper.vm.$nextTick(() => {
                            expect(wrapper.vm.tokens).toMatchObject([
                                {pair: 'tok1', change: '-73%', lastPrice: '123 MINTME', dayVolume: '321 MINTME'},
                                {pair: 'tok2', change: '-73%', lastPrice: '1230 MINTME', dayVolume: '3210 MINTME'},
                            ]);
                            done();
                        });
                    });
                });
             });
         });
     });
});
