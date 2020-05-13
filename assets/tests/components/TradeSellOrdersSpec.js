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
        traderFullName: 'firstName lastName',
        traderUrl: 'traderUrl',
        side: 1,
        owner: true,
    };

    it('hide the table and show message if no orders yet', () => {
        expect(wrapper.find('b-table').exists()).to.deep.equal(false);
        expect(wrapper.html().includes('No order was added yet')).to.deep.equal(true);
        wrapper.vm.ordersList = Array(2).fill(order);
        expect(wrapper.find('b-table').exists()).to.deep.equal(true);
        expect(wrapper.html().includes('No order was added yet')).to.deep.equal(false);
    });

    it('show arrow if orders > 7', () => {
        wrapper.vm.ordersList = Array(7).fill(order);
        expect(wrapper.find('.icon-arrows-down').exists()).to.deep.equal(false);
        wrapper.vm.ordersList = Array(8).fill(order);
        expect(wrapper.find('.icon-arrows-down').exists()).to.deep.equal(true);
    });
    it('show total amount correctly', () => {
        wrapper.vm.ordersList = Array(2).fill(order);
        expect(wrapper.vm.total).to.deep.equal(toMoney(4));
        wrapper.vm.ordersList.push(order);
        expect(wrapper.vm.total).to.deep.equal(toMoney(6));
    });
});
