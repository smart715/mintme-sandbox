import {createLocalVue, shallowMount} from '@vue/test-utils';
import TradeOrders from '../../js/components/trade/TradeOrders';
import {toMoney} from '../../js/utils';
import moxios from 'moxios';
import Axios from '../../js/axios';

chai.config.truncateThreshold = 0;

describe('TradeOrders', () => {
    beforeEach(() => {
        moxios.install();
    });
    afterEach(() => {
        moxios.uninstall();
    });
    const $routing = {generate: () => 'URL'};

    const localVue = createLocalVue();
    localVue.use(Axios);

    const wrapper = shallowMount(TradeOrders, {
        localVue,
        mocks: {
            $routing,
        },
        propsData: {
            ordersLoaded: false,
            buyOrders: [],
            sellOrders: [],
            market: {
                base: {
                    name: 'tok1',
                    symbol: 'tok1',
                    identifier: 'tok1',
                    subunit: 8,
                },
                quote: {
                    name: 'Webchain',
                    symbol: 'WEB',
                    identifier: 'WEB',
                    subunit: 8,
                },
            },
            userId: 1,
            isAnonymous: false,
        },
    });

    let order = {
        price: toMoney(2),
        amount: toMoney(2),
        maker: {
            id: 1,
            profile: {
                firstName: 'foo',
                lastName: 'bar',
                anonymous: false,
            },
        },
        side: 1,
        owner: false,
    };

    it('hide order components and show loading if not loaded', () => {
        wrapper.vm.ordersLoaded = false;

        expect(wrapper.find('font-awesome-icon').exists()).to.deep.equal(true);
        expect(wrapper.find('trade-sell-orders-stub').exists()).to.deep.equal(false);
        expect(wrapper.find('trade-buy-orders-stub').exists()).to.deep.equal(false);

        wrapper.vm.sellOrders = Array(2).fill(order);
        wrapper.vm.buyOrders = Array(2).fill(order);
        wrapper.vm.ordersLoaded = true;

        expect(wrapper.find('font-awesome-icon').exists()).to.deep.equal(false);
        expect(wrapper.find('trade-sell-orders-stub').exists()).to.deep.equal(true);
        expect(wrapper.find('trade-buy-orders-stub').exists()).to.deep.equal(true);
    });

    it('should group by price', function() {
        expect(wrapper.vm.filteredSellOrders).to.deep.equal([
            {price: toMoney(2), amount: toMoney(4), sum: toMoney(8),
                trader: 'foo ba...', traderFullName: 'foo bar', traderUrl: 'URL', side: 1, owner: true,
                isAnonymous: false},
        ]);

        wrapper.vm.sellOrders.push({...order, price: toMoney(3)});

        expect(wrapper.vm.filteredSellOrders).to.deep.equal([
            {price: toMoney(2), amount: toMoney(4), sum: toMoney(8),
                trader: 'foo ba...', traderFullName: 'foo bar', traderUrl: 'URL', side: 1, owner: true,
                isAnonymous: false},
            {price: toMoney(3), amount: toMoney(2), sum: toMoney(6),
                trader: 'foo ba...', traderFullName: 'foo bar', traderUrl: 'URL', side: 1, owner: true,
                isAnonymous: false},
        ]);
    });

    describe('truncate FullName correctly', function() {
        wrapper.vm.sellOrders = Array(2).fill(order);
        wrapper.vm.ordersLoaded = true;

        it('should add  Anonymous if the profile is null', function() {
            let newOrder = JSON.parse(JSON.stringify(order));
            newOrder.maker.profile = null;
            wrapper.vm.isAnonymous = true;
            wrapper.vm.sellOrders = [newOrder];

            expect(wrapper.vm.filteredSellOrders).to.deep.equal([
                {price: toMoney(2), amount: toMoney(2), sum: toMoney(4),
                    trader: 'Anonymous', traderFullName: 'Anonymous', traderUrl: '#', side: 1, owner: true,
                    isAnonymous: true},
            ]);
        });

        it('should add  Anonymous if the profile is set to Anonymous', function() {
            let newOrder = JSON.parse(JSON.stringify(order));
            wrapper.vm.sellOrders = [newOrder];

            expect(wrapper.vm.filteredSellOrders).to.deep.equal([
                {price: toMoney(2), amount: toMoney(2), sum: toMoney(4),
                    trader: 'foo ba...', traderFullName: 'foo bar', traderUrl: 'URL', side: 1, owner: true,
                    isAnonymous: false},
            ]);

            newOrder.maker.profile.anonymous = true;
            wrapper.vm.isAnonymous = true;

            expect(wrapper.vm.filteredSellOrders).to.deep.equal([
                {price: toMoney(2), amount: toMoney(2), sum: toMoney(4),
                    trader: 'Anonymous', traderFullName: 'Anonymous', traderUrl: '#', side: 1, owner: true,
                    isAnonymous: true},
            ]);
        });
    });
});
