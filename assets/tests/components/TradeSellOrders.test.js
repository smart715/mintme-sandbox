import {shallowMount} from '@vue/test-utils';
import TradeSellOrders from '../../js/components/trade/TradeSellOrders';
import {toMoney} from '../../js/utils';

describe('TradeSellOrders', () => {
    const wrapper = shallowMount(TradeSellOrders, {
        propsData: {
            ordersList: [],
            ordersLoaded: true,
            market: {
                base: {
                    name: 'Webchain',
                    symbol: 'WEB',
                    subunit: 4,
                    identifier: 'BTC',
                },
                quote: {
                    name: 'Token-name',
                    symbol: 'Token-name',
                    subunit: 4,
                    identifier: 'TOK',
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
        },
        mocks: {$t: (val) => val},
    });

    let order = {
        price: toMoney(2),
        amount: toMoney(2),
        sum: 4,
        trader: 'first..',
        traderUrl: 'traderUrl',
        side: 1,
        owner: true,
    };

    it('hide the table and show message if no orders yet', () => {
        expect(wrapper.html().includes('b-table')).toBe(false);
        expect(wrapper.html().includes('trade.sell_orders.no_orders')).toBe(true);
        wrapper.setProps({ordersList: Array(2).fill(order)});
        expect(wrapper.html().includes('b-table')).toBe(true);
        expect(wrapper.html().includes('trade.sell_orders.no_orders')).toBe(false);
    });

    it('show total amount correctly', () => {
        wrapper.setProps({ordersList: Array(2).fill(order)});
        expect(wrapper.vm.total).toBe(toMoney(4));
        wrapper.vm.ordersList.push(order);
        expect(wrapper.vm.total).toBe(toMoney(6));
    });
});
