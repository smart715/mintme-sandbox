import {shallowMount} from '@vue/test-utils';
import TradeBuyOrders from '../../js/components/trade/TradeBuyOrders';
import {toMoney} from '../../js/utils';

describe('TradeSellOrders', () => {
    const wrapper = shallowMount(TradeBuyOrders, {
        propsData: {
            ordersList: [],
            tokenName: 'TOK1',
            fields: {
                price: {
                    label: 'Price',
                    key: 'price',
                },
                amount: {
                    label: 'Amount',
                },
                sumWeb: {
                    label: 'Sum WEB',
                },
                trader: {
                    label: 'Trader',
                },
            },
            sortBy: 'name',
            sortDesc: true,
            precision: 8,
        },
    });

    let order = {
        price: toMoney(2),
        amount: toMoney(2),
        sumWeb: 4,
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
        expect(wrapper.vm.total).to.deep.equal(toMoney(8));
        wrapper.vm.ordersList.push(order);
        expect(wrapper.vm.total).to.deep.equal(toMoney(12));
    });
});
