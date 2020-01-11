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
    });
});
