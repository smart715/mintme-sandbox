import Vue from 'vue';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import TokenDelete from '../../js/components/token/TokenDelete';
import moxios from 'moxios';
import axios from 'axios';
Vue.use(Vuelidate);
Vue.use(Toasted);

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
            propsData: {
                loaded: false,
            },
        });

        expect(wrapper.find('span').classes('text-muted')).toBe(true);

        wrapper.setProps({loaded: true});

        wrapper.setProps({isTokenOverSoldLimit: true});
        wrapper.setProps({isTokenNotDeployed: false});
        expect(wrapper.find('span').classes('text-muted')).toBe(true);

        wrapper.setProps({isTokenOverSoldLimit: true});
        wrapper.setProps({isTokenNotDeployed: true});
        expect(wrapper.find('span').classes('text-muted')).toBe(true);

        wrapper.setProps({isTokenOverSoldLimit: false});
        wrapper.setProps({isTokenNotDeployed: false});
        expect(wrapper.find('span').classes('text-muted')).toBe(true);

        wrapper.setProps({isTokenOverSoldLimit: false});
        wrapper.setProps({isTokenNotDeployed: true});
        expect(wrapper.find('span').classes('text-muted')).toBe(false);
    });

    it('open TwoFactorModal for token deletion', () => {
        const wrapper = shallowMount(TokenDelete, {
            localVue: mockVue(),
            propsData: {
                isTokenOverSoldLimit: false,
                isTokenNotDeployed: true,
                loaded: true,
            },
        });
        wrapper.find('span').trigger('click');
        expect(wrapper.vm.showTwoFactorModal).toBe(true);
    });

    it('do not need to send auth code when 2fa enabled', () => {
        const wrapper = shallowMount(TokenDelete, {
            localVue: mockVue(),
            propsData: {
                twofa: true,
                loaded: true,
            },
        });
        expect(wrapper.vm.needToSendCode).toBe(false);
    });

    it('need to send auth code whe 2fa disabled', () => {
        const wrapper = shallowMount(TokenDelete, {
            localVue: mockVue(),
            propsData: {
                twofa: false,
                loaded: true,
            },
        });
        expect(wrapper.vm.needToSendCode).toBe(true);
    });

    it('do not need send auth code when it already sent', (done) => {
        const wrapper = shallowMount(TokenDelete, {
            localVue: mockVue(),
            propsData: {
                twofa: false,
                loaded: true,
            },
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
