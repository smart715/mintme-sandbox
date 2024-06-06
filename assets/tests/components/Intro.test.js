import {shallowMount, createLocalVue} from '@vue/test-utils';
import Intro from '../../js/components/coin/Intro.vue';
import axios from 'axios';
import moxios from 'moxios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
*/
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$logger = {error: () => {}};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

describe('Intro', () => {
    let wrapper;

    beforeEach(() => {
        moxios.install();
        jest.clearAllMocks();

        wrapper = shallowMount(Intro, {
            localVue: localVue,
        });
    });

    afterEach(() => {
        moxios.uninstall();
        wrapper.destroy();
    });

    it('should load total Users Registered correctly', async (done) => {
        await wrapper.setData({
            stats: {
                totalUsersRegistered: 0,
            },
        });

        moxios.stubRequest('get_total_users_registered', {
            status: 200,
            response: {
                count: 2,
            },
        });

        await wrapper.vm.loadTotalUsersRegistered();

        moxios.wait(() => {
            expect(wrapper.vm.stats.totalUsersRegistered).toBe(2);
            done();
        });
    });

    it('should load total wallets and transactions', async (done) => {
        await wrapper.setData({
            stats: {
                totalWallets: 0,
                totalTransactions: 0,
            },
        });

        moxios.stubRequest('get_total_wallets_and_transactions', {
            status: 200,
            response: {
                addresses: 2,
                transactions: 3,
            },
        });

        await wrapper.vm.loadTotalWalletsAndTransactions();

        moxios.wait(() => {
            expect(wrapper.vm.stats.totalWallets).toBe(2);
            expect(wrapper.vm.stats.totalTransactions).toBe(3);
            done();
        });
    });

    it('should load total network hashrate', async () => {
        await wrapper.setData({
            stats: {
                totalNetworkHashrate: 0,
            },
        });

        moxios.stubRequest('get_total_network_hashrate', {
            status: 200,
            response: {
                hashrate: '2.333',
            },
        });

        await wrapper.vm.loadTotalNetworkHashrate();

        moxios.wait(() => {
            expect(wrapper.vm.stats.totalNetworkHashrate).toBe('2.33');
        });
    });

    it('should loadStats correctly', () => {
        const loadTotalWalletsAndTransactionsSpy = jest.spyOn(wrapper.vm, 'loadTotalWalletsAndTransactions');
        const loadTotalNetworkHashrateSpy = jest.spyOn(wrapper.vm, 'loadTotalNetworkHashrate');
        const loadTotalUsersRegisteredSpy = jest.spyOn(wrapper.vm, 'loadTotalUsersRegistered');


        wrapper.vm.loadStats();

        expect(loadTotalWalletsAndTransactionsSpy).toHaveBeenCalled();
        expect(loadTotalNetworkHashrateSpy).toHaveBeenCalled();
        expect(loadTotalUsersRegisteredSpy).toHaveBeenCalled();
    });
});
