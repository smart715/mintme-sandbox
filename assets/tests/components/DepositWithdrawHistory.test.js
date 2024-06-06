import moment from 'moment';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import {status} from '../../js/storage/modules/websocket';
import DepositWithdrawHistory from '../../js/components/wallet/DepositWithdrawHistory';
import moxios from 'moxios';
import axios from 'axios';
import {TOKEN_DEFAULT_ICON_URL, WALLET_ITEMS_BATCH_SIZE} from '../../js/utils/constants';

const $routing = {
    generate: (val, params) => {
        return val
            + (params.name ? '-' + params.name : '')
            + (params.base ? '-' + params.base : '')
            + (params.quote ? '-' + params.quote : '');
    },
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    const $store = new Vuex.Store({
        modules: {status},
    });
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {
                retry: axios,
                single: axios,
            };
            Vue.prototype.$sortCompare = () => {};
            Vue.prototype.$routing = $routing;
            Vue.prototype.$store = $store;
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

const tableData = [
    {
        tradable: {name: 'testName', symbol: 'testWEB', blocked: true},
        url: 'testUrl',
        date: moment.utc([2020, 5, 20, 19, 44, 20]),
        fee: '0.500000000000000000',
        amount: '5.000000000000000000',
        status: {statusCode: 'testStatusCode'},
        type: {typeCode: 'typeCode'},
        image: TOKEN_DEFAULT_ICON_URL,
    },
    {
        tradable: {name: 'testName', symbol: 'testWEB'},
        url: 'testUrl',
        date: false,
        fee: false,
        amount: false,
        status: {statusCode: false},
        type: {typeCode: false},
        image: TOKEN_DEFAULT_ICON_URL,
    },
];

const expectTableData = [
    {
        tradable: {name: 'testName', symbol: 'testWEB', blocked: true},
        url: 'token_show_trade-testName',
        date: '2020-06-20T19:44:20.000Z',
        fee: '0.5',
        amount: '5',
        status: 'testStatusCode',
        type: 'typeCode',
        isToken: false,
        symbol: 'testWEB',
        image: undefined,
    },
    {
        tradable: {name: 'testName', symbol: 'testWEB'},
        url: 'token_show_trade-testName',
        date: null,
        fee: null,
        amount: null,
        status: null,
        type: null,
        isToken: false,
        symbol: 'testWEB',
        image: undefined,
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

/** @return {Wrapper<Vue>} */
function mockWrapper() {
    const localVue = mockVue();
    return shallowMount(DepositWithdrawHistory, {
        localVue,
        attachTo: document.body,
    });
}

describe('DepositWithdrawHistory', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should compute sanitizedHistory correctly', () => {
        const wrapper = mockWrapper();
        wrapper.vm.tableData = tableData;
        expect(wrapper.vm.sanitizedHistory).toEqual(expectTableData);
    });

    it('should compute noHistory correctly', () => {
        const wrapper = mockWrapper();
        wrapper.vm.tableData = [];
        expect(wrapper.vm.noHistory).toBe(true);
        wrapper.vm.tableData = tableData;
        expect(wrapper.vm.noHistory).toBe(false);
    });

    it('should compute loaded correctly', () => {
        const wrapper = mockWrapper();
        wrapper.vm.tableData = [];
        expect(wrapper.vm.loaded).toBe(true);
        wrapper.vm.tableData = null;
        expect(wrapper.vm.loaded).toBe(false);
    });

    it('should compute fieldsArray correctly', () => {
        const wrapper = mockWrapper();
        expect(wrapper.vm.fieldsArray).toEqual(Object.values((wrapper.vm.fields)));
    });

    it('should return correctly value when the function addDetailsForEmptyMessageToHistory() is called', () => {
        const wrapper = mockWrapper();
        expect(wrapper.vm.addDetailsForEmptyMessageToHistory([])).toEqual([{_showDetails: true}]);
        expect(wrapper.vm.addDetailsForEmptyMessageToHistory(['foo'])).toEqual(['foo']);
    });

    describe('updateTableData', () => {
        it(
            `should do $axios request and set tableData and currentPage correctly
            when result of $axios request is empty`,
            (done) => {
                const wrapper = mockWrapper();

                moxios.stubRequest('payment_history', {
                    status: 200,
                    response: [],
                });

                wrapper.vm.updateTableData();

                moxios.wait(() => {
                    expect(wrapper.vm.tableData).toEqual([]);
                    expect(wrapper.vm.currentPage).toBe(0);
                    done();
                });
            }
        );

        it(
            `should do $axios request and set tableData and currentPage correctly
            when result of $axios request is not empty`,
            (done) => {
                const wrapper = mockWrapper();

                moxios.stubRequest('payment_history', {
                    status: 200,
                    response: tableData,
                });

                wrapper.vm.updateTableData();

                moxios.wait(() => {
                    expect(wrapper.vm.tableData).toEqual([...tableData]);
                    expect(wrapper.vm.currentPage).toBe(1);
                    done();
                });
            }
        );
    });

    it('should return correctly value when the function sanitizeHistory() is called', () => {
        const wrapper = mockWrapper();
        wrapper.setData({tableData: tableData});

        expect(wrapper.vm.sanitizedHistory).toEqual(expectTableData);
    });

    it('should return correctly value when the function generatePairUrl() is called', () => {
        const wrapper = mockWrapper();
        expect(wrapper.vm.generatePairUrl({
            name: 'testName',
        })).toBe('token_show_trade-testName');
        expect(wrapper.vm.generatePairUrl({
            exchangeble: false,
            symbol: 'testWEB',
        })).toBe('coin-testWEB-MINTME');
        expect(wrapper.vm.generatePairUrl({
            exchangeble: true,
            symbol: 'testWEB',
            tradable: false,
        })).toBe('coin-BTC-MINTME');
        expect(wrapper.vm.generatePairUrl({
            exchangeble: true,
            symbol: 'testWEB',
            tradable: true,
        })).toBe('coin-BTC-testWEB');
    });

    describe('showSeeMoreButton', () => {
        it('should be false when there is no loaded orders', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DepositWithdrawHistory, {
                localVue,
            });

            expect(wrapper.vm.showSeeMoreButton).toBe(false);
        });

        it('should be true when full page is loaded', (done) => {
            const wrapper = mockWrapper();

            moxios.stubRequest('payment_history', {
                status: 200,
                response: fullPageResponse,
            });

            wrapper.vm.updateTableData();

            moxios.wait(() => {
                expect(wrapper.vm.showSeeMoreButton).toBe(true);
                done();
            });
        });

        it('should be false when loading next page', (done) => {
            const wrapper = mockWrapper();

            moxios.stubRequest('payment_history', {
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
                const localVue = mockVue();
                const wrapper = shallowMount(DepositWithdrawHistory, {
                    localVue,
                });

                moxios.stubRequest('payment_history', {
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
        const wrapper = mockWrapper();

        expect(wrapper.vm.currentPage).toBe(0);
        expect(wrapper.vm.nextPage).toBe(1);

        wrapper.vm.currentPage++;

        expect(wrapper.vm.currentPage).toBe(1);
        expect(wrapper.vm.nextPage).toBe(2);
    });

    describe('hasFee', () => {
        it('should be true when not zero and has currency', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DepositWithdrawHistory, {
                localVue,
            });

            expect(wrapper.vm.hasFee({
                fee: 2,
                feeCurrency: 'foo',
            })).toBe(true);
        });

        it('should be false when zero and has currency', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DepositWithdrawHistory, {
                localVue,
            });

            expect(wrapper.vm.hasFee({
                fee: '0',
                feeCurrency: 'foo',
            })).toBe(false);
        });

        it('should be false when not zero and not has currency', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DepositWithdrawHistory, {
                localVue,
            });

            expect(wrapper.vm.hasFee({
                fee: 5,
                feeCurrency: '',
            })).toBe(false);
        });
    });
});
