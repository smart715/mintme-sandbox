import {shallowMount, createLocalVue} from '@vue/test-utils';
import CoinBalance from '../../js/components/CoinBalance';
import Vuex from 'vuex';
import tradeBalance from '../../js/storage/modules/trade_balance';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });
    localVue.use(Vuex);
    return localVue;
}

describe('CoinBalance', () => {
    it('should update balance correctly', async () => {
        const wrapper = shallowMount(CoinBalance, {
            localVue: mockVue(),
            propsData: {
                coinName: 'TOK',
            },
            store: new Vuex.Store({
                modules: {
                    tradeBalance: {
                        ...tradeBalance,
                        state: {
                            balances: {
                                'TOK': {
                                    available: '1.000000',
                                    bonus: '2.00000',
                                },
                            },
                            serviceUnavailable: false,
                        },
                    },
                },
            }),
        });

        expect(wrapper.vm.balance).toBe('1');

        await wrapper.setProps({coinName: 'TOK', withBonus: true});
        expect(wrapper.vm.balance).toBe('3');
    });
});
