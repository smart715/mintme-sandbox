import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenTelegramChannel from '../../js/components/token/TokenTelegramChannel';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.component('b-tooltip', {});
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

describe('TokenTelegramChannel', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('save correct link', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenTelegramChannel, {
            localVue,
            data() {
                return {
                    showTelegramError: false,
                };
            },
            propsData: {
                editingTelegram: true,
                updateUrl: 'token_update',
            },
        });

        wrapper.find('input').setValue('https://t.me/joinchat/newtelegram');
        wrapper.vm.editTelegram();

        moxios.stubRequest('token_update', {
            status: 200,
        });

        moxios.wait(() => {
            expect(wrapper.vm.showTelegramError).toBe(false);
            expect(wrapper.emitted().saveTelegram[0]).toEqual(['https://t.me/joinchat/newtelegram']);
            done();
        });
    });

    it('do not save incorrect link', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenTelegramChannel, {
            localVue,
            data() {
                return {
                    showTelegramError: false,
                };
            },
            propsData: {
                editingTelegram: true,
            },
        });

        wrapper.find('input').setValue('incorrect_link');
        wrapper.vm.editTelegram();

        expect(wrapper.vm.showTelegramError).toBe(true);
    });

    it('show invitation text when link is not specified', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenTelegramChannel, {
            localVue,
            propsData: {
                editingTelegram: false,
            },
        });
        expect(wrapper.find('#telegram-link').text()).toBe('token.telegram.empty_address');
    });

    it('show link when specified', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenTelegramChannel, {
            localVue,
            propsData: {
                currentTelegram: 'https://t.me/joinchat/newtelegram',
                editingTelegram: false,
            },
        });
        expect(wrapper.find('#telegram-link').text()).toBe(wrapper.vm.currentTelegram);
    });
});
