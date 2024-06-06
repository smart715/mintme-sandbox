import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenPromotions from '../../js/components/token/TokenPromotions';
import moxios from 'moxios';
import axios from 'axios';
import moment from 'moment';
import {webSymbol} from '../../js/utils/constants';
import Vuex from 'vuex';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$te = () => true;
            Vue.prototype.$toasted = {show: (val) => val};
            Vue.prototype.$logger = {error: () => {}};
        },
    });

    return localVue;
}

/**
 * @return {Wrapper<Vue>}
 */
function createWrapper() {
    const localVue = mockVue();
    const wrapper = shallowMount(TokenPromotions, {
        propsData: propsForTestCorrectlyRenders,
        localVue,
        store: new Vuex.Store({
            modules: {
                user: {
                    namespaced: true,
                    getters: {
                        getId(state) {
                            return 1;
                        },
                    },
                },
                tradeBalance: {
                    namespaced: true,
                    getters: {
                        getBalances: () => {
                            return {'WEB': {available: 1}, 'ETH': {available: 2}};
                        },
                        isServiceUnavailable: () => false,
                    },
                },
            },
        }),
    });

    return wrapper;
}

const tariffs = [{duration: '1 year', cost: 0.1}, {duration: '5 years', cost: 0.5}];
const correctPromotionCostsResponse = {'1 year': {'ETH': 100, 'WEB': 200}, '5 years': {'ETH': 400, 'WEB': 600}};

const propsForTestCorrectlyRenders = {
    tokenName: 'TEST',
    disabledServicesConfig: '{}',
    tariffs: tariffs,
};

describe('TokenPromotions', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should be in loading state by default and load necessary data', (done) => {
        moxios.stubRequest('token_promotions_active', {
            status: 200,
            response: [{endDate: moment().format()}],
        });

        moxios.stubRequest('token_promotions_costs', {
            status: 200,
            response: correctPromotionCostsResponse,
        });

        const wrapper = createWrapper();

        expect(wrapper.vm.isLoading).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.isLoading).toBe(false);
            done();
        });
    });

    it('should show buy form when no active promotion', (done) => {
        moxios.stubRequest('token_promotions_active', {
            status: 200,
            response: null,
        });

        moxios.stubRequest('token_promotions_costs', {
            status: 200,
            response: correctPromotionCostsResponse,
        });

        const wrapper = createWrapper();

        moxios.wait(() => {
            expect(wrapper.findComponent('div.pb-1').exists()).toBe(true);
            done();
        });
    });

    it('should show active until when active subscription', (done) => {
        moxios.stubRequest('token_promotions_active', {
            status: 200,
            response: [{endDate: moment().format()}],
        });

        moxios.stubRequest('token_promotions_costs', {
            status: 200,
            response: correctPromotionCostsResponse,
        });

        const wrapper = createWrapper();

        moxios.wait(() => {
            expect(wrapper.vm.activePromotion).not.toBe(null);
            expect(wrapper.findComponent('div > div').text()).toContain('page.token_settings.token_promotion.active');
            done();
        });
    });

    it('should show correct tariff price', (done) => {
        moxios.stubRequest('token_promotions_active', {
            status: 200,
            response: null,
        });

        moxios.stubRequest('token_promotions_costs', {
            status: 200,
            response: correctPromotionCostsResponse,
        });

        const wrapper = createWrapper();

        moxios.wait(async () => {
            expect(wrapper.vm.selectedTariff).toBe(tariffs[0]);
            expect(wrapper.vm.selectedCurrency).toBe(webSymbol);
            expect(wrapper.findComponent('div > div').html()).toContain('200');
            expect(wrapper.findComponent('div > div').html()).toContain('MINTME');

            await wrapper.setData({selectedCurrency: 'ETH'});
            expect(wrapper.findComponent('div > div').html()).toContain('100');
            expect(wrapper.findComponent('div > div').html()).toContain('ETH');

            done();
        });
    });

    it('should correctly handle tariff label', () => {
        const wrapper = createWrapper();

        expect(wrapper.vm.getTariffLabel('1 year')).toBe('dynamic.token_promotions_tariff_1_year');
    });
});
