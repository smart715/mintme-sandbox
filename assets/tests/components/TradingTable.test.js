import {createLocalVue, shallowMount} from '@vue/test-utils';
import TradingTable from '../../js/components/trading/TradingTable';
// TODO: Improve tests and add more tests

const fields = {
    rank: {
        key: 'rank',
        field: 'rank',
        label: '#',
        sortable: true,
        help: '',
        tooltip: '',
        typeDef: {},
    },
    pair: {
        key: 'pair',
        field: 'pair',
        label: 'Name',
        class: 'pair-cell-trading',
        sortable: true,
        typeDef: {},
    },
    change: {
        key: 'change',
        field: 'change',
        label: 'Change',
        sortable: true,
        typeDef: {},
    },
    lastPrice: {
        label: 'Last Price',
        key: 'lastPrice',
        field: 'lastPrice',
        sortable: true,
        typeDef: {},
    },
    holders: {
        key: 'holders',
        label: 'Holders',
        field: 'holders',
        sortable: true,
        help: '',
        tooltip: '',
        typeDef: {},
    },
    volume: {
        key: 'monthVolume',
        label: '30d Volume',
        help: '',
        tooltip: '',
        field: 'monthVolume',
        sortable: true,
        typeDef: {},
    },
    marketCap: {
        key: 'buyDepth',
        label: 'Buy Depth',
        help: '',
        tooltip: '',
        field: 'buyDepth',
        sortable: true,
        typeDef: {},
    },
    tokenUrl: {
        key: 'trade',
        field: 'trade',
        label: 'Trade',
        sortable: true,
        typeDef: {},
    },
};

const marketFiltersProp = {
    userSelected: false,
    selectedFilter: 'deployedWeb',
    options: {
        deployedWeb: {
            key: 'deployedWeb',
            label: 'Deployed on MINTME',
        },
        deployedEth: {
            key: 'deployedEth',
            label: 'Deployed on ETH',
        },
        deployedBnb: {
            key: 'deployedBnb',
            label: 'Deployed on BSC',
        },
        airdrop: {
            key: 'airdrop',
            label: 'Active airdrops',
        },
        user: {
            key: 'user',
            label: 'Tokens I own',
        },
        searchByPhrase: {
            key: 'searchByPhrase',
        },
    },
};

const changeCrypto = {
    name: 'Webchain',
    symbol: 'WEB',
    subunit: 4,
    tradable: true,
    exchangeble: true,
    isToken: false,
    image: {},
    identifier: 'WEB',
};

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockTradingTable(props = {}) {
    return shallowMount(TradingTable, {
        localVue: mockVue(),
        propsData: {
            websocketUrl: 'testWebsocketUrl',
            enableUsd: true,
            fields: fields,
            sortByProp: 'rank',
            sortDescProp: true,
            marketFiltersProp: marketFiltersProp,
            sanitizedMarkets: {},
            currentPage: 1,
            tokensOnPage: 15,
            tokensProp: {},
            perPage: 10,
            cryptos: {
                WEB: {
                    symbol: 'WEB',
                },
            },
            tokenPromotions: [],
            ...props,
        },
    });
}

describe('Trading', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mockTradingTable();
    });

    afterEach(() => {
        wrapper.destroy();
    });

    describe('Verify that "volumesMarket" is working properly', () => {
        it('When showUsd is false', async () => {
            await wrapper.setProps({
                showUsd: false,
            });

            expect(wrapper.vm.volumesMarket).toStrictEqual({
                monthVolume: 'monthVolume',
                dayVolume: 'dayVolume',
            });
        });

        it('When showUsd is true', async () => {
            await wrapper.setProps({
                showUsd: true,
            });

            expect(wrapper.vm.volumesMarket).toStrictEqual({
                monthVolume: 'monthVolumeUSD',
                dayVolume: 'dayVolumeUSD',
            });
        });
    });

    it('emits an event setActiveMarketCap', () => {
        wrapper.vm.setActiveMarketCap('BTC');

        expect(wrapper.emitted()['set-active-marketCap'][0]).toEqual(['BTC']);
    });

    it('emits an event sortChanged', () => {
        wrapper.vm.sortChanged('ASC');

        expect(wrapper.emitted()['sort-changed'][0]).toEqual(['ASC']);
    });

    it('emits an event toggleActiveVolume', () => {
        wrapper.vm.toggleActiveVolume('22');

        expect(wrapper.emitted()['toggle-active-volume'][0]).toEqual(['22']);
    });

    it('emits an event changeVolumeCapOption', () => {
        wrapper.vm.changeVolumeCapOption('option');

        expect(wrapper.emitted()['set-active-volume-cap'][0]).toEqual(['option']);
    });

    it('emits an event tokensForFilters', () => {
        wrapper.vm.tokensForFilters(['token-1', 'token-2']);

        expect(wrapper.emitted()['tokens-for-filters'][0]).toEqual([['token-1', 'token-2']]);
    });

    it('emits an event toggleCrypto', () => {
        wrapper.vm.toggleCrypto(changeCrypto);

        expect(wrapper.emitted()['toggle-crypto'][0]).toEqual([changeCrypto]);
    });

    it('emits an event dispatchToggleShowMore', () => {
        wrapper.vm.dispatchToggleShowMore(22);

        expect(wrapper.emitted()['toggle-show-more'][0]).toEqual([22]);
    });

    it('emits an event toggleFilter', () => {
        wrapper.vm.toggleFilter('deployed');

        expect(wrapper.emitted()['toggle-filter'][0]).toEqual(['deployed']);
    });

    it('emits an event toggleSearch', () => {
        wrapper.vm.toggleSearch('moonpark');

        expect(wrapper.emitted()['toggle-search'][0]).toEqual(['moonpark']);
    });

    it('emits an event ToggleShowMore', () => {
        wrapper.vm.toggleShowMore();

        expect(wrapper.emitted()['toggle-show-more'][0]).toEqual([]);
    });

    describe('marketCapFormatter() function should return ', () => {
        it('dash(-) if market is for token and monthVolume less than minimumVolumeForMarketcap', () => {
            const item = {
                base: 'MINTME',
                monthVolume: 9,
            };
            expect(wrapper.vm.marketCapFormatter('9', 0, item)).toBe('9');
        });

        it('value if market is not for token', () => {
            const item = {
                base: 'foo',
                monthVolume: 10,
            };
            expect(wrapper.vm.marketCapFormatter('10', 0, item)).toBe('10');
        });

        it('value if monthVolume not less than minimumVolumeForMarketcap', () => {
            const item = {
                base: 'MINTME',
                monthVolume: 10,

            };
            expect(wrapper.vm.marketCapFormatter('9', 0, item)).toBe('9');
        });
    });
    describe('pair tooltip ', () => {
        it('should return true if token name length less than 17', () => {
            const pair = 'a'.repeat(10);

            expect(wrapper.vm.disabledTooltip(pair)).toBe(true);
        });

        it('should return false if token name length greater than or equal 17', () => {
            const pair = 'a'.repeat(20);

            expect(wrapper.vm.disabledTooltip(pair)).toBe(false);
        });
    });
    describe('it calculates change row class correctly', () => {
        it('should return null when value is zero', () => {
            expect(wrapper.vm.getClassForChangeRow(0)).toBe('');
        });

        it('should return text-success when value less then zero', () => {
            expect(wrapper.vm.getClassForChangeRow(1)).toBe('text-success');
        });

        it('should return text-danger when value more than zero', () => {
            expect(wrapper.vm.getClassForChangeRow(-1)).toBe('text-danger');
        });
    });
});
