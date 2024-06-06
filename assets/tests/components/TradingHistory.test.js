import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradingHistory from '../../js/components/wallet/TradingHistory';
import axios from 'axios';
import {WALLET_ITEMS_BATCH_SIZE} from '../../js/utils/constants';
import moxios from 'moxios';

const localVue = createLocalVue();
localVue.use({
    install(Vue, options) {
        Vue.prototype.$routing = {generate: (val) => val};
        Vue.prototype.$axios = {retry: axios, single: axios};
        Vue.prototype.$sortCompare = () => {};
        Vue.prototype.$t = (val) => val;
        Vue.prototype.$logger = {error: () => {}};
    },
});

const tableData = [
    {
        timestamp: 1551876719.890195,
        side: 2,
        amount: '5.000000000000000000',
        price: '1.000000000000000000',
        fee: '0.500000000000000000',
        market: {
            base: {
                subunit: 4,
            },
            quote: {
                name: 'user110token',
            },
            currencySymbol: 'WEB',
            hiddenName: 'TOK000000000010WEB',
        },
    },
    {
        timestamp: 1551876719.890195,
        side: 1,
        amount: '5.000000000000000000',
        price: '1.000000000000000000',
        fee: '0.050000000000000000',
        market: {
            base: {
                subunit: 4,
            },
            quote: {
                name: 'user110token',
            },
            currencySymbol: 'WEB',
            hiddenName: 'TOK000000000010WEB',
        },
    },
    {
        timestamp: 1551876704.610206,
        side: 2,
        amount: '5.000000000000000000',
        price: '1.000000000000000000',
        fee: '0.500000000000000000',
        market: {
            base: {
                subunit: 4,
            },
            quote: {
                name: 'user110token',
            },
            currencySymbol: 'WEB',
            hiddenName: 'TOK000000000010WEB',
        },
    },
    {
        timestamp: 1551876704.610206,
        side: 1,
        amount: '5.000000000000000000',
        price: '1.000000000000000000',
        fee: '0.050000000000000000',
        market: {
            base: {
                subunit: 4,
            },
            quote: {
                name: 'user110token',
            },
            currencySymbol: 'WEB',
            hiddenName: 'TOK000000000010WEB'},
    },
];

const fullPageResponse = [];

while (fullPageResponse.length < WALLET_ITEMS_BATCH_SIZE) {
    fullPageResponse.push({...tableData[0]});
}

const notFullPageResponse = [
    {...tableData[0]},
    {...tableData[0]},
];

describe('TradingHistory', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('must determine history', () => {
        const wrapper = shallowMount(TradingHistory, {
            localVue,
        });
        wrapper.setData({tableData});

        expect(wrapper.vm.hasHistory).toBe(true);
    });

    describe('tableData', () => {
        it(`should do $axios request and set tableData and currentPage correctly
        when result of $axios request is not empty`, (done) => {
            const wrapper = shallowMount(TradingHistory, {
                localVue,
            });

            moxios.stubRequest('executed_user_orders', {
                status: 200,
                response: fullPageResponse,
            });

            wrapper.vm.updateTableData();

            moxios.wait(() => {
                expect(wrapper.vm.tableData).toEqual([...fullPageResponse]);
                expect(wrapper.vm.currentPage).toBe(1);
                done();
            });
        });

        it(`should do $axios request and set tableData and currentPage correctly
        when result of $axios request is empty`, (done) => {
            const wrapper = shallowMount(TradingHistory, {
                localVue,
            });

            moxios.stubRequest('executed_user_orders', {
                status: 200,
                response: [],
            });

            wrapper.vm.updateTableData();

            moxios.wait(() => {
                expect(wrapper.vm.tableData).toEqual([]);
                expect(wrapper.vm.currentPage).toBe(0);
                done();
            });
        });
    });

    describe('showSeeMoreButton', () => {
        it('should be false when there is no loaded orders', () => {
            const wrapper = shallowMount(TradingHistory, {
                localVue,
            });

            expect(wrapper.vm.showSeeMoreButton).toBe(false);
        });

        it('should be true when full page is loaded', (done) => {
            moxios.stubRequest('executed_user_orders', {
                status: 200,
                response: fullPageResponse,
            });

            const wrapper = shallowMount(TradingHistory, {
                localVue,
            });

            moxios.wait(() => {
                expect(wrapper.vm.showSeeMoreButton).toBe(true);
                done();
            });
        });

        it('should be false when loading next page', (done) => {
            const wrapper = shallowMount(TradingHistory, {
                localVue,
            });

            moxios.stubRequest('executed_user_orders', {
                status: 200,
                response: fullPageResponse,
            });

            moxios.wait(() => {
                wrapper.vm.updateTableData();

                expect(wrapper.vm.showSeeMoreButton).toBe(false);
                done();
            });
        });

        it('should be false when loaded not full page (last page)', (done) => {
            moxios.wait(() => {
                const wrapper = shallowMount(TradingHistory, {
                    localVue,
                });

                moxios.stubRequest('executed_user_orders', {
                    status: 200,
                    response: notFullPageResponse,
                });

                moxios.wait(() => {
                    expect(wrapper.vm.showSeeMoreButton).toBe(false);
                    done();
                });
            });
        });
    });

    it('should compute nextPage correctly', () => {
        const wrapper = shallowMount(TradingHistory, {
            localVue,
        });

        expect(wrapper.vm.currentPage).toBe(0);
        expect(wrapper.vm.nextPage).toBe(1);

        wrapper.vm.currentPage++;

        expect(wrapper.vm.currentPage).toBe(1);
        expect(wrapper.vm.nextPage).toBe(2);
    });
});
