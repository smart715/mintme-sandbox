import {createLocalVue, shallowMount} from '@vue/test-utils';
import Register from '../../js/components/Register';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import Vue from 'vue';
import Vuelidate from 'vuelidate';
Vue.use(Vuelidate);

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.component('font-awesome-icon', {});
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
        },
    });
    return localVue;
}

describe('Register', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    const localVue = mockVue();
    localVue.use(Vuex);

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

    it('should render register and login form correctly', () => {
        const wrapper = shallowMount(Register, {
            store,
            localVue,
            propsData: {
                googleRecaptchaSiteKey: 'any_fake_key_data',
            },
        });
        expect(wrapper.find('#login').exists()).toBe(true);
        expect(wrapper.find('#register').exists()).toBe(true);
        expect(wrapper.vm.loginFormLoaded).toBe(true);
        expect(wrapper.vm.loginForm).toBe(false);
    });
});
