import Vue from 'vue';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import TokenDelete from '../../js/components/token/TokenDelete';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import tokenStatistics from '../../js/storage/modules/token_statistics';

Vue.use(Vuelidate);
Vue.use(Toasted);
Vue.use(Vuex);

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

const store = new Vuex.Store({
    modules: {
        tokenStatistics: {
            ...tokenStatistics,
            state: {
                tokenDeleteSoldLimit: 100000,
            },
        },
    },
});

describe('TokenDelete', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('renders correctly with assigned props', () => {
        const wrapper = shallowMount(TokenDelete, {
            localVue: mockVue(),
            data: () => ({
                soldOnMarket: null,
            }),
            propsData: {
                isTokenNotDeployed: false,
            },
            store,
        });

        expect(wrapper.vm.loaded).toBe(false);
        expect(wrapper.vm.btnDisabled).toBe(true);
        expect(wrapper.find('span').classes('text-muted')).toBe(true);

        wrapper.setData({soldOnMarket: 120000});
        wrapper.setProps({isTokenNotDeployed: false});
        expect(wrapper.vm.loaded).toBe(true);
        expect(wrapper.vm.btnDisabled).toBe(true);
        expect(wrapper.find('span').classes('text-muted')).toBe(true);

        wrapper.setData({soldOnMarket: 2500});
        wrapper.setProps({isTokenNotDeployed: false});
        expect(wrapper.vm.btnDisabled).toBe(true);
        expect(wrapper.find('span').classes('text-muted')).toBe(true);

        wrapper.setProps({isTokenNotDeployed: true});
        expect(wrapper.vm.btnDisabled).toBe(false);
        expect(wrapper.find('span').classes('text-muted')).toBe(false);
    });

    it('open TwoFactorModal for token deletion', () => {
        const wrapper = shallowMount(TokenDelete, {
            localVue: mockVue(),
            data: () => ({
                soldOnMarket: 0,
            }),
            propsData: {
                isTokenNotDeployed: true,
            },
            store,
        });
        wrapper.find('span').trigger('click');
        expect(wrapper.vm.showTwoFactorModal).toBe(true);
    });

    it('do not need to send auth code when 2fa enabled', () => {
        const wrapper = shallowMount(TokenDelete, {
            localVue: mockVue(),
            propsData: {
                twofa: true,
            },
            store,
        });
        expect(wrapper.vm.needToSendCode).toBe(false);
    });

    it('need to send auth code whe 2fa disabled', () => {
        const wrapper = shallowMount(TokenDelete, {
            localVue: mockVue(),
            propsData: {
                twofa: false,
            },
            store,
        });
        expect(wrapper.vm.needToSendCode).toBe(true);
    });

    it('do not need send auth code when it already sent', (done) => {
        const wrapper = shallowMount(TokenDelete, {
            localVue: mockVue(),
            propsData: {
                twofa: false,
            },
            store,
        });

        wrapper.vm.sendConfirmCode();

        moxios.stubRequest('token_send_code', {
            status: 200,
            response: {message: 'message'},
        });

        moxios.wait(() => {
            expect(wrapper.vm.needToSendCode).toBe(false);
            done();
        });
    });
});
