import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradeSellOrders from '../../js/components/trade/TradeSellOrders';
import {toMoney} from '../../js/utils';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
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
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        ordersList: [],
        ordersLoaded: true,
        market: {
            base: {
                name: 'Webchain',
                symbol: 'WEB',
                subunit: 4,
                identifier: 'BTC',
                image: {
                    url: require('../../img/BTC.svg'),
                },
            },
            quote: {
                name: 'Token-name',
                symbol: 'Token-name',
                subunit: 4,
                identifier: 'TOK',
                image: {
                    url: require('../../img/default_token_avatar.svg'),
                },
            },
        },
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
};

const order = {
    price: toMoney(2),
    amount: toMoney(2),
    sum: 4,
    trader: 'first..',
    traderUrl: 'traderUrl',
    side: 1,
    owner: true,
};

describe('TradeSellOrders', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(TradeSellOrders, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    afterEach(() => {
        wrapper = null;
    });

    it('hide the table and show message if no orders yet', async () => {
        expect(wrapper.html().includes('b-table')).toBe(false);
        expect(wrapper.html().includes('trade.sell_orders.no_orders')).toBe(true);

        await wrapper.setProps({ordersList: Array(2).fill(order)});
        expect(wrapper.html().includes('b-table')).toBe(true);
        expect(wrapper.html().includes('trade.sell_orders.no_orders')).toBe(false);
    });

    it('show total amount correctly', async () => {
        await wrapper.setProps({ordersList: Array(2).fill(order)});
        expect(wrapper.vm.totalAmount).toBe(toMoney(4));

        wrapper.vm.ordersList.push(order);
        expect(wrapper.vm.totalAmount).toBe(toMoney(6));
    });
});
