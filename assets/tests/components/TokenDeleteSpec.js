import Vue from 'vue';
import {createLocalVue, mount} from '@vue/test-utils';
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
    localVue.use(axios);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
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
        const wrapper = mount(TokenDelete, {
            propsData: {isTokenExchanged: true},
        });
        expect(wrapper.find('span').attributes('disabled')).to.equal('disabled');
    });

    it('renders correctly with assigned props 2', () => {
        const wrapper = mount(TokenDelete, {
            propsData: {isTokenExchanged: false},
        });
        expect(wrapper.find('span').attributes('disabled')).to.equal(undefined);
    });

    it('open TwoFactorModal for token deletion', () => {
        const wrapper = mount(TokenDelete);
        wrapper.find('span').trigger('click');
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(true);
    });

    it('do not need to send auth code when 2fa enabled', () => {
        const wrapper = mount(TokenDelete, {
            propsData: {twofa: true},
        });
        expect(wrapper.vm.needToSendCode).to.equal(false);
    });

    it('need to send auth code whe 2fa disabled', () => {
        const wrapper = mount(TokenDelete, {
            propsData: {twofa: false},
        });
        expect(wrapper.vm.needToSendCode).to.equal(true);
    });

    it('do not need send auth code when it already sent', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenDelete, {
            localVue,
            propsData: {twofa: false},
        });

        wrapper.vm.sendConfirmCode();

        moxios.stubRequest('token_send_code', {
            status: 202,
            response: {message: 'message'},
        });

        moxios.wait(() => {
            expect(wrapper.vm.needToSendCode).to.equal(false);
            done();
        });
    });
});
