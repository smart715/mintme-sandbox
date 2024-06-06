import {createLocalVue, shallowMount} from '@vue/test-utils';
import ActiveOrders from '../../js/components/wallet/ActiveOrders';
import axios from 'axios';
import moxios from 'moxios';
import sortCompare from '../../js/table_sort_plugin';
import {WALLET_ITEMS_BATCH_SIZE, WSAPI} from '../../js/utils/constants';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(sortCompare);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
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
function createActiveOrdersProps(props = {}) {
    return {
        userId: 1,
        isUserBlocked: false,
        websocketUrl: '',
        markets: testMarket,
        ...props,
    };
}

const order = {
    id: 481,
    market: 'BTC/WEB',
    source: '',
    type: 1,
    side: 1,
    user: 1,
    ctime: 1541763111.4951439,
    mtime: 1541763111.4951439,
    price: '10',
    amount: '0.1',
    taker_fee: '0.1',
    maker_fee: '0.1',
    left: '0.1',
    deal_stock: '0',
    deal_money: '0',
    deal_fee: '0',
    unused_fee: '0',
};

const testMarket = {
    base: {
        name: 'Bitcoin',
        symbol: 'BTC',
        subunit: 8,
        tradable: true,
        exchangeble: true,
        isToken: false,
        image: {
            url: '/media/default_btc.svg',
        },
        identifier: 'BTC',
    },
    quote: {
        name: 'Webchain',
        symbol: 'WEB',
        subunit: 4,
        tradable: true,
        exchangeble: true,
        isToken: false,
        image: {
            url: '/media/default_mintme.svg',
        },
        identifier: 'WEB',
    },
    identifier: 'WEBBTC',
};

const fullPageResponse = [];
let orderId = 400;

while (fullPageResponse.length < WALLET_ITEMS_BATCH_SIZE) {
    fullPageResponse.push({...order, id: orderId++});
}

describe('ActiveOrders', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(ActiveOrders, {
            localVue: localVue,
            propsData: createActiveOrdersProps(),
            data() {
                return {
                    tableData: [],
                };
            },
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('formatter name should return correct data', () => {
        const name = 'name';

        expect(wrapper.vm.fields.name.formatter(name)).toEqual({
            full: name,
            truncate: 'name',
        });
    });

    it('should be false when there is no loaded orders', () => {
        expect(wrapper.vm.showSeeMoreButton).toBe(false);
    });

    it('showSeeMoreButton should be true when there is loaded orders', async () => {
        await wrapper.setData({
            loading: false,
            tableData: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        });

        expect(wrapper.vm.showSeeMoreButton).toBe(true);
    });

    it('totalRows should be 0 when there is no loaded orders', () => {
        expect(wrapper.vm.totalRows).toBe(0);
    });

    it('totalRow should be tableData.length when there is loaded orders', async () => {
        await wrapper.setData({
            tableData: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        });

        expect(wrapper.vm.totalRows).toBe(3);
    });

    it('should return market names', async () => {
        await wrapper.setData({
            markets: [
                {identifier: 'market1'},
                {identifier: 'market2'},
                {identifier: 'market3'},
            ],
        });

        expect(wrapper.vm.marketNames).toEqual(['market1', 'market2', 'market3']);
    });

    it('should return fields array', () => {
        expect(wrapper.vm.fieldsArray).toMatchObject([
            {key: 'date'},
            {key: 'type'},
            {key: 'name'},
            {key: 'amount'},
            {key: 'price'},
            {key: 'total'},
            {key: 'action'},
        ]);
    });

    it('translation context return text', async () => {
        await wrapper.setData({
            currentRow: {
                name: 'name',
                amount: 1,
                price: 2,
            },
        });

        expect(wrapper.vm.translationsContext).toEqual({
            name: 'name',
            amount: 1,
            price: 2,
        });
    });

    it('translation context return empty object', async () => {
        await wrapper.setData({
            currentRow: {},
        });

        expect(wrapper.vm.translationsContext).toEqual({
            name: '-',
            amount: 0,
            price: 0,
        });
    });

    describe('Markets', () => {
        it('should call getMarket method', async () => {
            moxios.stubRequest('markets', {
                status: 200,
                response: order,
            });

            await wrapper.vm.getMarkets();

            expect(wrapper.vm.markets).toEqual(Object.values(order));
        });

        it('should call getMarket method with error', async () => {
            moxios.stubRequest('markets', {
                status: 500,
                response: [],
            });

            await wrapper.vm.getMarkets();

            expect(wrapper.vm.markets).toBe(null);
        });
    });

    describe('tableData', () => {
        it('set tableData & currentPage if request its request not empty', async () => {
            moxios.stubRequest('orders', {
                status: 200,
                response: fullPageResponse,
            });

            await wrapper.vm.updateTableData();

            expect(wrapper.vm.tableData).toEqual([...fullPageResponse]);
            expect(wrapper.vm.currentPage).toBe(1);
        });

        it('call updateTableData with empty response', async () => {
            moxios.stubRequest('orders', {
                status: 200,
                response: [],
            });

            await wrapper.vm.updateTableData();

            expect(wrapper.vm.tableData).toEqual([]);
            expect(wrapper.vm.currentPage).toBe(0);
        });

        it('call updateTableData with error', async () => {
            moxios.stubRequest('orders', {
                status: 500,
                response: [],
            });

            await wrapper.vm.updateTableData();

            expect(wrapper.vm.currentPage).toBe(0);
        });

        it('call to updateTable should omits duplicate data', async () => {
            moxios.stubRequest('orders', {
                status: 200,
                response: [{id: 1, name: 'Order 1'}, {id: 2, name: 'Order 2'}],
            });

            await wrapper.vm.updateTableData();

            moxios.stubRequest('orders', {
                status: 200,
                response: [{id: 2, name: 'Order 2'}],
            });

            await wrapper.vm.updateTableData();

            expect(wrapper.vm.tableData).toEqual([
                {id: 1, name: 'Order 1'},
                {id: 2, name: 'Order 2'},
            ]);
        });
    });

    it('should return correct data in history', async () => {
        const orderHistory = {
            timestamp: 'timestamp',
            side: 'side',
            market: {
                base: {
                    subunit: 4,
                },
                quote: {
                    symbol: 'BNB',
                    image: 'https://cuteImg.com',
                },
            },
            amount: 10,
            price: 20,
            id: 2,
        };

        await wrapper.setData({
            tableData: [orderHistory],
        });

        expect(wrapper.vm.history[0].total).toEqual('200');
        expect(wrapper.vm.history[0].name).toContain('BNB');
    });

    it('should return trade url', () => {
        const market = {
            quote: {
                exchangeble: true,
                tradable: true,
            },
            base: {
                symbol: 'BTC',
            },
        };

        expect(wrapper.vm.generatePairUrl(market)).toEqual('coin');
    });

    it('should return token url', () => {
        const market = {
            quote: {
                exchangeble: false,
                tradable: false,
                name: 'token',
            },
            base: {
                symbol: 'BTC',
            },
        };

        expect(wrapper.vm.generatePairUrl(market)).toEqual('token_show_trade');
    });

    it('should removeOrderModal with blocked order', () => {
        const item = {
            blocked: true,
        };

        wrapper.vm.removeOrderModal(item);

        expect(wrapper.vm.currentRow).toEqual({});
    });

    it('should removeOrderModal with not blocked order', () => {
        const item = {
            blocked: false,
            action: 'action',
        };

        wrapper.vm.removeOrderModal(item);

        expect(wrapper.vm.currentRow).toEqual(item);
        expect(wrapper.vm.actionUrl).toEqual(item.action);
    });

    it('should return market from name', async () => {
        await wrapper.setData({
            markets: [
                {identifier: 'market1'},
                {identifier: 'market2'},
                {identifier: 'market3'},
            ],
        });

        expect(wrapper.vm.getMarketFromName('market2')).toEqual({identifier: 'market2'});
    });

    describe('updateOrders', () => {
        it('should let order in the tableData array', async () => {
            const getMarketFromNameSpy = jest.spyOn(wrapper.vm, 'getMarketFromName').mockReturnValue(testMarket);

            await wrapper.setData({
                tableData: [order],
            });

            wrapper.vm.updateOrders(order, WSAPI.order.status.PUT);

            expect(wrapper.vm.tableData).toHaveLength(2);
            expect(getMarketFromNameSpy).toHaveBeenCalledWith(order.market);
        });

        it('should update order in the tableData array', async () => {
            await wrapper.setData({
                tableData: [order],
            });

            wrapper.vm.updateOrders(order, WSAPI.order.status.UPDATE);

            expect(wrapper.vm.tableData).toHaveLength(1);
        });

        it('should update order with empty tableData array', async () => {
            await wrapper.setData({
                tableData: [],
            });

            wrapper.vm.updateOrders(order, WSAPI.order.status.UPDATE);

            expect(wrapper.vm.tableData).toEqual([]);
        });

        it('should remove order with empty tableData array', async () => {
            await wrapper.setData({
                tableData: [],
            });

            wrapper.vm.updateOrders(order, WSAPI.order.status.FINISH);

            expect(wrapper.vm.tableData).toEqual([]);
        });

        it('should remove order in the tableData array', async () => {
            await wrapper.setData({
                tableData: [order],
            });

            wrapper.vm.updateOrders(order, WSAPI.order.status.FINISH);

            expect(wrapper.vm.tableData).toHaveLength(0);
        });
    });

    it('should return pair name', () => {
        const baseSymbol = {
            symbol: 'BTC',
        };

        const quoteSymbol = {
            symbol: 'ETH',
            image: {
                url: 'https://superCripto.com/images/eth.png',
            },
        };

        expect(wrapper.vm.pairNameFunc(baseSymbol, quoteSymbol)).toContain('ETH');
        expect(wrapper.vm.pairNameFunc(baseSymbol, quoteSymbol)).toContain('BTC');
    });

    it('should return pair name with token', () => {
        const baseSymbol = {
            symbol: 'BNB',
        };

        const quoteSymbol = {
            symbol: 'WEB',
            image: {
                url: 'https://superCripto.com/images/eth.png',
            },
            isToken: true,
        };

        expect(wrapper.vm.pairNameFunc(baseSymbol, quoteSymbol)).toContain('MINTME');
        expect(wrapper.vm.pairNameFunc(baseSymbol, quoteSymbol)).toContain('BNB');
    });

    describe('removeOrder', () => {
        it('should remove order', async () => {
            await wrapper.setData({
                currentRow: {
                    id: 1,
                },
                actionUrl: 'remove-order',
            });

            moxios.stubRequest('remove-order', {
                status: 200,
                response: {
                    data: {
                        success: true,
                    },
                },
            });

            await wrapper.vm.removeOrder();

            expect(wrapper.vm.currentRow).toEqual({id: 1});
        });

        it('should remove order with err.response.data.message', async () => {
            await wrapper.setData({
                currentRow: {
                    id: 1,
                },
                actionUrl: 'remove-order',
            });

            moxios.stubRequest('remove-order', {
                status: 403,
                response: {
                    message: 'error',
                },
            });

            jest.spyOn(wrapper.vm, 'notifyError').mockReturnValue('error');
            await wrapper.vm.removeOrder();

            expect(wrapper.vm.notifyError()).toEqual('error');
        });

        it('should remove order with error response else', async () => {
            await wrapper.setData({
                currentRow: {
                    id: 1,
                },
                actionUrl: 'remove-order',
            });

            moxios.stubRequest('remove-order', {
                status: 500,
                response: {
                    error: 'error',
                },
            });

            jest.spyOn(wrapper.vm, 'notifyError').mockReturnValue('error');
            await wrapper.vm.removeOrder();

            expect(wrapper.vm.notifyError()).toEqual('error');
        });
    });
});
