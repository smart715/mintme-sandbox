import {createLocalVue, shallowMount} from '@vue/test-utils';
import '../vueI18nfix.js';
import LoginSignupSwitcher from '../../js/components/LoginSignupSwitcher';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import Vuelidate from 'vuelidate';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
        },
    });
    localVue.use(Vuelidate);
    localVue.use(Vuex);
    return localVue;
}

describe('LoginSignupSwitcher', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    const localVue = mockVue();

    const store = new Vuex.Store({
        modules: {
            websocket: {
                namespaced: true,
                actions: {
                    addOnOpenHandler: () => {},
                    addMessageHandler: () => {},
                },
            },
        },
    });

    it('should render register and login form correctly', (done) => {
        // @TODO find out how to make Vue.extend work on jest so that we can assert the mounting onf the register component
        const wrapper = shallowMount(LoginSignupSwitcher, {
            store,
            localVue,
            propsData: {
                googleRecaptchaSiteKey: 'any_fake_key_data',
            },
            mocks: {
                $t: () => {},
            },
        });

        moxios.stubRequest('login', {
            status: 200,
            response: 'login',
        });

        moxios.stubRequest('register', {
            status: 200,
            response: 'register',
        });

        moxios.wait(() => {
            expect(wrapper.find('#login-form-container').html()).toContain('login');
            done();
        });
    });
});
