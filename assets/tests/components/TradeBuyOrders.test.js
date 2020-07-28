import {shallowMount} from '@vue/test-utils';
import TradeBuyOrders from '../../js/components/trade/TradeBuyOrders';
import {toMoney} from '../../js/utils';

describe('TradeBuyOrders', () => {
    const wrapper = shallowMount(TradeBuyOrders, {
        propsData: {
            ordersList: [],
            tokenName: 'TOK1',
            ordersLoaded: true,
            fields: {
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
            sortBy: 'name',
            sortDesc: true,
            basePrecision: 8,
        },
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
        expect(wrapper.find('b-table').exists()).toBe(false);
        expect(wrapper.html().includes('No order was added yet')).toBe(true);
        wrapper.vm.ordersList = Array(2).fill(order);
        expect(wrapper.find('b-table').exists()).toBe(true);
        expect(wrapper.html().includes('No order was added yet')).toBe(false);
    });

    it('show total amount correctly', () => {
        wrapper.vm.ordersList = Array(2).fill(order);
        expect(wrapper.vm.total).toBe(toMoney(8));
        wrapper.vm.ordersList.push(order);
        expect(wrapper.vm.total).toBe(toMoney(12));
    });
});
