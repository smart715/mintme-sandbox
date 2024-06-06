import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenReleasePeriod from '../../js/components/token/TokenReleasePeriod';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import tokenStatistics from '../../js/storage/modules/token_statistics';
import tokenSettings from '../../js/storage/modules/token_settings';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    localVue.use(Vuex);

    return localVue;
}

/**
 * @param {Object} modules
 * @return {Wrapper<Vuex.Store>}
 */
function mockStore(modules) {
    const store = new Vuex.Store({
        modules: {
            ...modules,
        },
    });

    return store;
}

/**
 * @param {Object} props
 * @param {Object} data
 * @param {Object} modules
 * @return {Wrapper<Vue>}
 */
function createWrapper(props = {}, data = {}, modules = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(TokenReleasePeriod, {
        store: mockStore(modules),
        localVue,
        propsData: {
            ...props,
        },
        data() {
            return {
                ...data,
            };
        },
    });

    return wrapper;
}

describe('TokenReleasePeriod', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('showAreaUnlockedTokens', () => {
        it('should return true if released is different than 100 ', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({released: 10});
            expect(wrapper.vm.showAreaUnlockedTokens).toBe(true);
        });

        it('should return false if released equals 100', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({released: 100});
            expect(wrapper.vm.showAreaUnlockedTokens).toBe(false);
        });
    });

    describe('releasedDisabled', () => {
        it('should return true if isTokenNotDeployed is false', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({hasLockin: true, releasePeriod: 10});
            await wrapper.setProps({isTokenExchanged: true, isTokenNotDeployed: false});
            expect(wrapper.vm.releasedDisabled).toBe(true);
        });

        it('should return true if hasLockin is true, releasePeriod != 0 and isTokenExchanged is true', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({hasLockin: true, releasePeriod: 10});
            await wrapper.setProps({isTokenExchanged: true, isTokenNotDeployed: true});
            expect(wrapper.vm.releasedDisabled).toBe(true);
        });

        it('should return false if isTokenExchanged is true', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({hasLockin: false, releasePeriod: 10});
            await wrapper.setProps({isTokenExchanged: true, isTokenNotDeployed: true});
            expect(wrapper.vm.releasedDisabled).toBe(false);
        });

        it('should return false if isTokenExchanged is true and hasLockin is false', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({hasLockin: false, releasePeriod: 10});
            await wrapper.setProps({isTokenExchanged: true, isTokenNotDeployed: true});
            expect(wrapper.vm.releasedDisabled).toBe(false);
        });

        it('should return false if isTokenExchanged is true and releasePeriod = 0', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({hasLockin: true, releasePeriod: 0});
            await wrapper.setProps({isTokenExchanged: true, isTokenNotDeployed: true});
            expect(wrapper.vm.releasedDisabled).toBe(false);
        });

        it('should return false if isTokenExchanged is true and isTokenExchanged is false', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({hasLockin: true, releasePeriod: 10});
            await wrapper.setProps({isTokenExchanged: false, isTokenNotDeployed: true});
            expect(wrapper.vm.releasedDisabled).toBe(false);
        });
    });

    describe('releasePeriodDisabled', () => {
        it('should return true if isTokenNotDeployed is false', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isTokenNotDeployed: false});
            expect(wrapper.vm.releasePeriodDisabled).toBe(true);
        });

        it('should return false if isTokenNotDeployed is true', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isTokenNotDeployed: true});
            expect(wrapper.vm.releasePeriodDisabled).toBe(false);
        });
    });

    describe('updateTokenStatistics', () => {
        it('should call setTokenExchangeAmount on successful response', async (done) => {
            const wrapper = createWrapper({}, {}, {
                tokenStatistics: tokenStatistics,
            });
            const commitSpy = jest.spyOn(wrapper.vm.$store, 'commit');

            moxios.stubRequest('token_exchange_amount', {
                status: 200,
                response: 120,
            });

            await wrapper.vm.updateTokenStatistics({
                releasePeriod: '2030-01-01',
                hourlyRate: 10,
                releasedAmount: 200,
                frozenAmount: 50,
            });

            moxios.wait(() => {
                expect(commitSpy).toHaveBeenCalledWith(
                    'tokenStatistics/setStats',
                    {
                        'frozenAmount': 50,
                        'hourlyRate': 10,
                        'releasePeriod': '2030-01-01',
                        'releasedAmount': 200,
                    },
                    undefined
                );
                expect(commitSpy).toHaveBeenCalledWith(
                    'tokenStatistics/setTokenExchangeAmount',
                    120,
                    undefined
                );
                done();
            });
        });

        it('should call logger.error on error response', async (done) => {
            const wrapper = createWrapper({}, {}, {
                tokenStatistics,
            });
            const loggerSpy = jest.spyOn(wrapper.vm.$logger, 'error');
            const commitSpy = jest.spyOn(wrapper.vm.$store, 'commit');

            moxios.stubRequest('token_exchange_amount', {
                status: 500,
            });

            await wrapper.vm.updateTokenStatistics({
                releasePeriod: '2030-01-01',
                hourlyRate: 10,
                releasedAmount: 200,
                frozenAmount: 50,
            });

            moxios.wait(() => {
                expect(commitSpy).toHaveBeenCalledWith(
                    'tokenStatistics/setStats',
                    {
                        'frozenAmount': 50,
                        'hourlyRate': 10,
                        'releasePeriod': '2030-01-01',
                        'releasedAmount': 200,
                    },
                    undefined
                );
                expect(loggerSpy).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('saveReleasePeriod', () => {
        it('should call setHasReleasePeriod and updateTokenStatistics on successful response', async (done) => {
            const wrapper = createWrapper({}, {}, {
                tokenStatistics,
                tokenSettings,
            });
            const commitSpy = jest.spyOn(wrapper.vm.$store, 'commit');
            const updateTokenStatisticsSpy = jest.spyOn(wrapper.vm, 'updateTokenStatistics');

            moxios.stubRequest('lock_in', {
                status: 200,
                response: 20,
            });

            await wrapper.vm.saveReleasePeriod();

            expect(wrapper.vm.isSaving).toBe(true);

            moxios.wait(() => {
                expect(commitSpy).toHaveBeenCalledWith(
                    'tokenSettings/setHasReleasePeriod',
                    true,
                    undefined
                );
                expect(updateTokenStatisticsSpy).toHaveBeenCalledWith(20);
                expect(wrapper.vm.isSaving).toBe(false);
                done();
            });
        });

        it('should call notifyError on error response', async (done) => {
            const wrapper = createWrapper();
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('lock_in', {
                status: 500,
                response: {
                    data: {
                        message: 'error',
                    },
                },
            });

            await wrapper.vm.saveReleasePeriod();

            expect(wrapper.vm.isSaving).toBe(true);

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                expect(wrapper.vm.isSaving).toBe(false);
                done();
            });
        });
    });

    describe('refreshSliders', () => {
        it('should call refresh', async () => {
            const refresh = jest.fn();
            const wrapper = createWrapper();

            wrapper.vm.$refs['released-slider'].refresh = refresh;
            wrapper.vm.$refs['release-period-slider'].refresh = refresh;

            await wrapper.vm.refreshSliders();

            expect(refresh).toHaveBeenCalledTimes(2);
        });
    });
});
