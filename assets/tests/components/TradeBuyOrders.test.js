import Vuex from 'vuex';
import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradeBuyOrders from '../../js/components/trade/TradeBuyOrders';
import {toMoney} from '../../js/utils';
import moxios from 'moxios';
import axios from 'axios';
import Decimal from 'decimal.js';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createBuyOrders(props = {}) {
    return {
        ordersList: [],
        tokenName: 'TOK1',
        ordersLoaded: true,
        fields: [
            {
                price: {
                    label: 'Price',
                    key: 'price',
                },
                amount: {
                    label: 'Amount',
                },
                sum: {
                    label: 'Sum WEB',
                },
                trader: {
                    label: 'Trader',
                },
            },
        ],
        sortBy: 'name',
        sortDesc: true,
        basePrecision: 8,
        ...props,
    };
}

/**
 * @return {Vuex.Store}
 */
function createSharedTestStore() {
    return new Vuex.Store({
        modules: {
            rates: {
                namespaced: true,
                getters: {
                    getRates: () => {
                        return {
                            'TOK1': 0.001,
                        };
                    },
                },
            },
            market: {
                namespaced: true,
                getters: {
                    getCurrentMarket: () => {
                        return {
                            base: {
                                precision: 8,
                            },
                        };
                    },
                },
            },
            minOrder: {
                namespaced: true,
                getters: {
                    getMinOrder: () => 2,
                },
            },
        },
    });
}

const order = {
    price: toMoney(2),
    amount: toMoney(2),
    sum: 4,
    trader: 'first..',
    traderUrl: 'traderUrl',
    side: 1,
    owner: true,
};

describe('TradeBuyOrders', () => {
    let store;
    let wrapper;

    beforeEach(() => {
        store = createSharedTestStore();

        wrapper = shallowMount(TradeBuyOrders, {
            localVue,
            store: store,
            propsData: createBuyOrders(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('hide the table and show message if no orders yet', async () => {
        expect(wrapper.html().includes('b-table')).toBe(false);
        expect(wrapper.html().includes('trade.buy_orders.no_orders')).toBe(true);
        await wrapper.setProps({ordersList: Array(2).fill(order)});
        expect(wrapper.html().includes('b-table')).toBe(true);
        expect(wrapper.html().includes('trade.sell_orders.no_orders')).toBe(false);
    });

    it('show total amount correctly', async () => {
        await wrapper.setProps({ordersList: Array(2).fill(order)});
        expect(wrapper.vm.totalSum).toBe(toMoney(8));
        wrapper.vm.ordersList.push(order);
        expect(wrapper.vm.totalSum).toBe(toMoney(12));
    });

    it('should return total sum', async () => {
        await wrapper.setProps(({totalBuyOrders: new Decimal(2)}));

        expect(wrapper.vm.totalSum).toEqual('2');
    });

    it('should return rate', () => {
        expect(wrapper.vm.rate).toEqual(1);
    });

    it('should return sum with usd mode', async () => {
        expect(wrapper.vm.sum('2', '2')).toEqual('$4');
    });

    it('should emit modal event', () => {
        wrapper.vm.removeOrderModal(order);

        expect(wrapper.emitted().modal).toBeTruthy();
        expect(wrapper.emitted().modal[0][0]).toEqual(order);
    });

    it('should emit update-data event', () => {
        wrapper.vm.updateTableData();

        expect(wrapper.emitted()['update-data']).toBeTruthy();
    });

    it('should return rowClass', () => {
        expect(wrapper.vm.rowClass({highlightClass: 'highlightClass'}, 'row')).toEqual('buy-order highlightClass');
        expect(wrapper.vm.rowClass({highlightClass: 'highlightClass'}, 'col')).toEqual('buy-order');
    });

    it('should return result of currencyConvert', async () => {
        expect(wrapper.vm.currencyConvert('10.500', '5.000', 4)).toEqual('$52.5');
    });

    it('should return tokenName', async () => {
        await wrapper.setProps({tokenName: 'TOK2'});

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.tokenName).toEqual('TOK2');
        expect(wrapper.vm.tooltipKey).toEqual(1);
    });
});
