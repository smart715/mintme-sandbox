import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenTelegramChannel from '../../js/components/token/TokenTelegramChannel';
import moxios from 'moxios';
import axios from 'axios';
import {MInput} from '../../js/components/UI';

const localVue = mockVue();

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
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        editingTelegram: true,
        updateUrl: 'token_update',
        ...props,
    };
}

describe('TokenTelegramChannel', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(TokenTelegramChannel, {
            localVue: localVue,
            propsData: createSharedTestProps(),
            data() {
                return {
                    showTelegramError: false,
                };
            },
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('save correct link', (done) => {
        moxios.stubRequest('token_update', {
            status: 200,
        });

        wrapper.findComponent(MInput).vm.$emit('input', 'https://t.me/newtelegram');
        wrapper.vm.editTelegram();

        moxios.wait(() => {
            expect(wrapper.vm.showTelegramError).toBe(false);
            expect(wrapper.emitted().saveTelegram[0]).toEqual(['https://t.me/newtelegram']);
            done();
        });
    });

    it('do not save incorrect link', () => {
        wrapper.findComponent(MInput).vm.$emit('input', 'incorrect_link');
        wrapper.vm.editTelegram();

        expect(wrapper.vm.showTelegramError).toBe(true);
    });

    it('show invitation text when link is not specified', async () => {
        await wrapper.setProps({
            editingTelegram: false,
        });

        expect(wrapper.findComponent('#telegram-link').text()).toBe('token.telegram.empty_address');
    });

    it('show link when specified', async () => {
        await wrapper.setProps({
            currentTelegram: 'https://t.me/newtelegram',
            editingTelegram: false,
        });

        expect(wrapper.findComponent('#telegram-link').text()).toBe(wrapper.vm.currentTelegram);
    });

    describe('Verify that the "toggleEdit" event is emitted correctly', () => {
        it('When "editing" is false', async () => {
            await wrapper.setData({
                editing: false,
            });

            wrapper.vm.toggleEdit();

            expect(wrapper.vm.editing).toBe(true);
            expect(wrapper.emitted('toggleEdit')).toBeTruthy();
            expect(wrapper.emitted('toggleEdit')[0]).toEqual(['telegram']);
        });

        it('When "editing" is true', async () => {
            await wrapper.setData({
                editing: true,
            });

            wrapper.vm.toggleEdit();

            expect(wrapper.vm.editing).toBe(false);
            expect(wrapper.emitted('toggleEdit')).toBeFalsy();
        });
    });
});
