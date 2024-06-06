import {createLocalVue, shallowMount} from '@vue/test-utils';
import Trading, {tradingColumnsSort, tradingTableColumns} from '../../js/components/trading/Trading';
import axios from 'axios';
import moxios from 'moxios';
import Vuex from 'vuex';

// TODO: Improve tests and add more tests

const filterForTokens = {
    deployed_first: 1,
    deployed_only_mintme: 2,
    airdrop_only: 3,
    deployed_only_eth: 4,
    search_by_phrase: 7,
};

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

const $logger = {error: (val, params) => val, success: (val, params) => val};

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val, params = {}) => {
                return val;
            }};
            Vue.prototype.$toasted = {show: () => false};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = $logger;
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} data
 * @return {Wrapper<Vue>}
 */
function mockTrading(props = {}, data = {}) {
    Trading.methods.fetchConversionRates = () => false;
    Trading.methods.updateSanitizedMarkets = () => false;
    Trading.methods.listenForMarketsUpdate = () => false;

    return shallowMount(Trading, {
        localVue: mockVue(),
        propsData: {
            websocketUrl: 'testWebsocketUrl',
            enableUsd: true,
            filterForTokens: filterForTokens,
            sortBy: 'rank',
            cryptos: {
                'WEB': {
                    symbol: 'WEB',
                },
                'ETH': {
                    symbol: 'ETH',
                },
            },
            deployBlockchains: ['WEB', 'BNB', 'CRO', 'ETH'],
            sort: 'rank',
            marketsProp: {},
            ...props,
            cryptoTopListMarketKeys: ['BTC'],
        },

        data() {
            return {
                updateMarketsDelay: 0,
                ...data,
            };
        },
        store: new Vuex.Store({
            modules: {
                crypto: {
                    namespaced: true,
                    getters: {
                        getCryptosMap: () => {
                            return {
                                'BTC': {},
                                'WEB': {},
                                'ETH': {},
                            };
                        },
                    },
                },
                websocket: {
                    namespaced: true,
                    actions: {
                        addOnOpenHandler: () => {},
                        addMessageHandler: () => {},
                    },
                },
            },
        }),
    });
}

describe('Trading', () => {
    describe('data field', () => {
        describe(':tokens', () => {
            describe('when fetch markets from server', () => {
                const wrapper = mockTrading({minimumVolumeForMarketcap: Infinity});
                wrapper.vm.markets = Object.assign({}, markets);

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
                                volumeDonation: '0',
                                deal: '32',
                                dealDonation: '32',
                            },
                        ],
                        id: null,
                    });

                    wrapper.vm.$nextTick(() => {
                        wrapper.vm.$nextTick(() => {
                            expect(wrapper.vm.sanitizedMarketsOnTop).toMatchObject([
                                {pair: 'WEB/BTC', change: '-73', lastPrice: '12', dayVolume: '64'},
                            ]);
                            done();
                        });
                    });
                });
            });
        });
    });

    describe('sortChanged', () => {
        let wrapper;
        const newSort = [
            {
                field: tradingTableColumns.change,
                type: 'desc',
            },
        ];

        beforeEach( () => {
            wrapper = mockTrading();
            wrapper.vm.updateMarkets = jest.fn();
        });

        it('should properly change sortBy and sortDesc', () => {
            wrapper.vm.sortBy = tradingColumnsSort.pair;
            wrapper.vm.sortDesc = false;
            wrapper.vm.sortChanged(newSort);

            expect(wrapper.vm.sortBy).toBe(tradingColumnsSort.change);
            expect(wrapper.vm.sortDesc).toBe(true);
        });

        it('should discard \'newest deployed\' filter if it\'s selected', () => {
            wrapper.vm.selectedFilters = [
                wrapper.vm.marketFilters.options.deployedETH.key,
                wrapper.vm.marketFilters.options.newest_deployed.key,
            ];
            wrapper.vm.sortChanged(newSort);

            expect(wrapper.vm.selectedFilters).toEqual([
                wrapper.vm.marketFilters.options.deployedETH.key,
            ]);
        });

        it('should set page = 1', () => {
            wrapper.vm.currentPage = 2;
            wrapper.vm.sortChanged(newSort);

            expect(wrapper.vm.currentPage).toBe(1);
        });

        it('should call updateMarkets', () => {
            wrapper.vm.sortChanged(newSort);

            expect(wrapper.vm.updateMarkets.mock.calls.length).toBe(1);
        });
    });

    describe('toggleCrypto', () => {
        let wrapper;

        beforeEach( () => {
            wrapper = mockTrading();
            wrapper.vm.updateMarkets = jest.fn();
        });

        it('should properly change selected crypto', () => {
            wrapper.vm.currentCrypto = btc.symbol;
            wrapper.vm.toggleCrypto(web.symbol);

            expect(wrapper.vm.currentCrypto).toEqual(web.symbol);
        });

        it('should set page = 1', () => {
            wrapper.vm.currentPage = 2;
            wrapper.vm.toggleCrypto(web.symbol);

            expect(wrapper.vm.currentPage).toBe(1);
        });

        it('should clear searchPhrase', () => {
            wrapper.vm.searchPhrase = 'moonpark';
            wrapper.vm.toggleCrypto(web.symbol);

            expect(wrapper.vm.searchPhrase).toBe('');
        });

        it('should call updateMarkets', () => {
            wrapper.vm.toggleCrypto(web.symbol);

            expect(wrapper.vm.updateMarkets.mock.calls.length).toBe(1);
        });
    });

    describe('toggleFilter', () => {
        let wrapper;

        beforeEach( () => {
            wrapper = mockTrading();
            wrapper.vm.updateMarkets = jest.fn();
        });

        it('should add filter to selectedFilters if it`s not selected yet', () => {
            wrapper.vm.selectedFilters = [wrapper.vm.marketFilters.options.deployedETH.key];
            wrapper.vm.toggleFilter(wrapper.vm.marketFilters.options.deployedWEB.key);

            expect(wrapper.vm.selectedFilters).toEqual(
                [
                    wrapper.vm.marketFilters.options.deployedETH.key,
                    wrapper.vm.marketFilters.options.deployedWEB.key,
                ]
            );
        });

        it('should discard filter from selectedFilters if it`s already selected', () => {
            wrapper.vm.selectedFilters = [
                wrapper.vm.marketFilters.options.deployedETH.key,
                wrapper.vm.marketFilters.options.deployedWEB.key,
            ];
            wrapper.vm.toggleFilter(wrapper.vm.marketFilters.options.deployedETH.key);

            expect(wrapper.vm.selectedFilters)
                .toEqual([wrapper.vm.marketFilters.options.deployedWEB.key]);
        });

        it('shouldn\'t unselect filter, if it\'s blockchain filter  and only 1 blockchain is selected', () => {
            wrapper.vm.selectedBlockchains = 1;
            wrapper.vm.selectedFilters = [wrapper.vm.marketFilters.options.deployedETH.key];

            wrapper.vm.toggleFilter(wrapper.vm.marketFilters.options.deployedETH.key);

            expect(wrapper.vm.selectedFilters)
                .toEqual([wrapper.vm.marketFilters.options.deployedETH.key]);
        });

        it('should set page = 1', () => {
            wrapper.vm.currentPage = 2;
            wrapper.vm.toggleFilter(wrapper.vm.marketFilters.options.deployedWEB.key);

            expect(wrapper.vm.currentPage).toBe(1);
        });

        it('should clear searchPhrase', () => {
            wrapper.vm.searchPhrase = 'moonpark';
            wrapper.vm.toggleFilter(wrapper.vm.marketFilters.options.deployedWEB.key);

            expect(wrapper.vm.searchPhrase).toBe('');
        });

        it('should call updateMarkets', () => {
            wrapper.vm.toggleFilter(wrapper.vm.marketFilters.options.deployedWEB.key);

            expect(wrapper.vm.updateMarkets.mock.calls.length).toBe(1);
        });
    });

    describe('toggleSearch', () => {
        let wrapper;

        beforeEach( () => {
            wrapper = mockTrading();
            wrapper.vm.updateMarkets = jest.fn();
        });

        it('should properly change searchPhrase', () => {
            wrapper.vm.searchPhrase = '';
            wrapper.vm.toggleSearch('moonpark');

            expect(wrapper.vm.searchPhrase).toBe('moonpark');
        });

        it('should clear searchPhrase, if new searchPhrase is too short', () => {
            wrapper.vm.searchPhraseMinLength = 3;
            wrapper.vm.searchPhrase = 'moon';
            wrapper.vm.toggleSearch('mo');

            expect(wrapper.vm.searchPhrase).toBe('');
        });

        it('should set page = 1', () => {
            wrapper.vm.currentPage = 2;
            wrapper.vm.toggleSearch('moonpark');

            expect(wrapper.vm.currentPage).toBe(1);
        });

        it('should call updateMarkets', () => {
            wrapper.vm.toggleSearch('moonpark');

            expect(wrapper.vm.updateMarkets.mock.calls.length).toBe(1);
        });
    });

    describe('createParams', () => {
        let wrapper;

        beforeEach( () => {
            wrapper = mockTrading();
        });

        it('should properly build params object without searchPhrase (1)', async () => {
            wrapper.vm.searchPhrase = '';
            wrapper.vm.sortBy = 'best sort';
            wrapper.vm.sortDesc = true;
            wrapper.vm.currentPage = 213;
            wrapper.vm.currentCrypto = web.symbol;
            wrapper.vm.selectedFilters = [
                wrapper.vm.marketFilters.options.deployedETH.key,
                wrapper.vm.marketFilters.options.newest_deployed.key,
            ];
            await wrapper.setProps({
                filterForTokens: {
                    deployed_only_eth: 400,
                    newest_deployed: 221,
                },
            });

            const expectedParams = {
                page: 213,
                sort: 'best sort',
                crypto: 'WEB',
                order: 'DESC',
                filters: [400, 221],
                type: 'tokens',
            };

            expect(wrapper.vm.createParams()).toEqual(expectedParams);
        });

        it('should properly build params object without searchPhrase (2)', async () => {
            wrapper.vm.searchPhrase = '';
            wrapper.vm.sortBy = wrapper.vm.fields.pair.key;
            wrapper.vm.sortDesc = true;
            wrapper.vm.currentPage = 1;
            wrapper.vm.currentCrypto = btc.symbol;
            wrapper.vm.selectedFilters = [
                wrapper.vm.marketFilters.options.newest_deployed.key,
                wrapper.vm.marketFilters.options.deployedETH.key,

            ];
            await wrapper.setProps({
                filterForTokens: {
                    deployed_only_eth: 300,
                    newest_deployed: 100,
                },
            });

            const expectedParams = {
                page: 1,
                sort: wrapper.vm.fields.pair.key,
                crypto: 'BTC',
                order: 'DESC',
                filters: [100, 300],
                type: 'tokens',
            };

            expect(wrapper.vm.createParams()).toEqual(expectedParams);
        });

        it('should properly build params object with searchPhrase', () => {
            wrapper.vm.searchPhrase = 'moonpark';
            wrapper.vm.sortBy = 'best sort';
            wrapper.vm.sortDesc = false;
            wrapper.vm.currentPage = 1;
            wrapper.vm.currentCrypto = btc.symbol;
            wrapper.vm.selectedFilters = [
                wrapper.vm.marketFilters.options.newest_deployed.key,
                wrapper.vm.marketFilters.options.deployedETH.key,

            ];
            wrapper.setProps({
                filterForTokens: {
                    deployed_only_eth: 300,
                    newest_deployed: 100,
                },
            });

            const expectedParams = {
                page: 1,
                sort: 'best sort',
                order: 'ASC',
                searchPhrase: 'moonpark',
                type: 'tokens',
            };

            expect(wrapper.vm.createParams()).toEqual(expectedParams);
        });
    });

    describe('updateMarkets', () => {
        beforeEach( () => {
            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('should set tableLoading to false after request is done', (done) => {
            const wrapper = mockTrading();

            wrapper.vm.updateMarkets();
            moxios.stubRequest('markets_info', {
                status: 200,
                response: {
                    markets: [1, 2, 3],
                },
            });

            moxios.wait(() => {
                expect(wrapper.vm.tableLoading).toBe(false);
                done();
            });
        });
    });

    it('selectedBlockchainsAmount should return properly value', () => {
        const wrapper = mockTrading();

        wrapper.vm.selectedFilters = [
            wrapper.vm.marketFilters.options.deployedWEB.key,
            wrapper.vm.marketFilters.options.deployedETH.key,
            wrapper.vm.marketFilters.options.user_owns.key,
        ];

        expect(wrapper.vm.selectedBlockchainsAmount).toBe(2);
    });
});
