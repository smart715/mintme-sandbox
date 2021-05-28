import moment from 'moment';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import {status} from '../../js/storage/modules/websocket';
import DepositWithdrawHistory from '../../js/components/wallet/DepositWithdrawHistory';
import moxios from 'moxios';
import axios from 'axios';

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
        },
    });
    return localVue;
};

const tableData = [
    {
        tradable: {name: 'testName', symbol: 'testWEB', blocked: true},
        url: 'testUrl',
        date: moment([2020, 5, 20, 19, 44, 20]),
        fee: '0.500000000000000000',
        amount: '5.000000000000000000',
        status: {statusCode: 'testStatusCode'},
        type: {typeCode: 'typeCode'},
    },
    {
        tradable: {name: 'testName', symbol: 'testWEB'},
        url: 'testUrl',
        date: false,
        fee: false,
        amount: false,
        status: {statusCode: false},
        type: {typeCode: false},
    },
];

const expectTableData = [
    {
        tradable: {name: 'testName', symbol: 'testWEB', blocked: true},
        url: 'token_show-testName',
        date: '20.06.2020 19:44:20',
        fee: '0.5',
        amount: '5',
        status: 'testStatusCode',
        type: 'typeCode',
        symbol: 'testWEB',
    },
    {
        tradable: {name: 'testName', symbol: 'testWEB'},
        url: 'token_show-testName',
        date: null,
        fee: null,
        amount: null,
        status: null,
        type: null,
        symbol: 'testWEB',
    },
];

describe('DepositWithdrawHistory', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should compute sanitizedHistory correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DepositWithdrawHistory, {
            localVue,
        });
        wrapper.vm.tableData = tableData;
        expect(wrapper.vm.sanitizedHistory).toEqual(expectTableData);
    });

    it('should compute noHistory correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DepositWithdrawHistory, {
            localVue,
        });
        wrapper.vm.tableData = [];
        expect(wrapper.vm.noHistory).toBe(true);
        wrapper.vm.tableData = tableData;
        expect(wrapper.vm.noHistory).toBe(false);
    });

    it('should compute loaded correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DepositWithdrawHistory, {
            localVue,
        });
        wrapper.vm.tableData = [];
        expect(wrapper.vm.loaded).toBe(true);
        wrapper.vm.tableData = null;
        expect(wrapper.vm.loaded).toBe(false);
    });

    it('should compute fieldsArray correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DepositWithdrawHistory, {
            localVue,
        });
        expect(wrapper.vm.fieldsArray).toEqual(Object.values((wrapper.vm.fields)));
    });

    it('should return correctly value when the function addDetailsForEmptyMessageToHistory() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DepositWithdrawHistory, {
            localVue,
        });
        expect(wrapper.vm.addDetailsForEmptyMessageToHistory([])).toEqual([{_showDetails: true}]);
        expect(wrapper.vm.addDetailsForEmptyMessageToHistory(['foo'])).toEqual(['foo']);
    });

    describe('updateTableData', () => {
        it('should do $axios request and set tableData and currentPage correctly when result of $axios request is empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(DepositWithdrawHistory, {
                localVue,
            });
            wrapper.vm.updateTableData();

            moxios.stubRequest('payment_history', {
                status: 200,
                response: null,
            });

            moxios.wait(() => {
                expect(wrapper.vm.tableData).toBe(null);
                expect(wrapper.vm.currentPage).toBe(3);
                done();
            });
        });

        it('should do $axios request and set tableData and currentPage correctly when result of $axios request is not empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(DepositWithdrawHistory, {
                localVue,
            });
            wrapper.vm.updateTableData();

            moxios.stubRequest('payment_history', {
                status: 200,
                response: tableData,
            });

            moxios.wait(() => {
                expect(wrapper.vm.tableData).toEqual([...tableData, ...tableData]);
                expect(wrapper.vm.currentPage).toBe(3);
                done();
            });
        });
    });

    it('should return correctly value when the function sanitizeHistory() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DepositWithdrawHistory, {
            localVue,
        });
        wrapper.setData({tableData: tableData});

        expect(wrapper.vm.sanitizedHistory).toEqual(expectTableData);
    });

    it('should return correctly value when the function generatePairUrl() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DepositWithdrawHistory, {
            localVue,
        });
        expect(wrapper.vm.generatePairUrl({
            name: 'testName',
        })).toBe('token_show-testName');
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
});
