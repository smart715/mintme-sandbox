import {createLocalVue, shallowMount} from '@vue/test-utils';
import TradingFilters from '../../js/components/trading/TradingFilters.vue';
import Vuex from 'vuex';

const marketFilters = {
    userSelected: false,
    selectedFilters: ['deployedWeb'],
    options: {
        deployed: {
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
        newest_deployed: {
            key: 'newest_deployed',
            label: 'Newest',
        },
        airdrop: {
            key: 'airdrop',
            label: 'Active airdrops',
        },
        user_owns: {
            key: 'user_owns',
            label: 'Tokens I own',
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
    localVue.use(Vuex);
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
function wrapperTradingFilter(props = {}) {
    return shallowMount(TradingFilters, {
        localVue: mockVue(),
        propsData: {
            showUsd: true,
            userId: 1,
            marketFilters: marketFilters,
            currentCryptos: [],
            ...props,
        },
    });
}

describe('TradingFilters', () => {
    let wrapper;

    beforeEach( () => {
        wrapper = wrapperTradingFilter();
    });

    describe('toggleCrypto', () => {
        it('should emit toggle-crypto event', () => {
            wrapper.vm.toggleCrypto(changeCrypto);

            expect(wrapper.emitted()['toggle-crypto'][0]).toEqual([changeCrypto]);
        });

        it('should clear searchPhrase', () => {
            wrapper.vm.searchPhrase = 'mo';
            wrapper.vm.toggleCrypto(changeCrypto);

            expect(wrapper.vm.searchPhrase).toBe('');
        });
    });

    describe('toggleFilter', () => {
        it('should emit toggle-filter event', () => {
            wrapper.vm.toggleFilter('deployedWeb');

            expect(wrapper.emitted()['toggle-filter'][0]).toEqual(['deployedWeb']);
        });

        it('should clear searchPhrase', () => {
            wrapper.vm.searchPhrase = 'mo';
            wrapper.vm.toggleFilter('deployedWeb');

            expect(wrapper.vm.searchPhrase).toBe('');
        });
    });

    it('toggleSearch should emit toggle-search event', () => {
        wrapper.vm.toggleSearch('moonpark');

        expect(wrapper.emitted()['toggle-search'][0]).toEqual(['moonpark']);
    });
});
