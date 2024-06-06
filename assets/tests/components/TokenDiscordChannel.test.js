import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenDiscordChannel from '../../js/components/token/TokenDiscordChannel';
import moxios from 'moxios';
import axios from 'axios';

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
        currentDiscord: '',
        editingDiscord: true,
        tokenName: 'TokenJasm',
        ...props,
    };
}

describe('TokenDiscordChannel', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(TokenDiscordChannel, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('save correct link', async (done) => {
        await wrapper.setProps({
            editingDiscord: true,
            updateUrl: 'token_update',
        });

        await wrapper.setData({
            showDiscordError: false,
            newDiscord: 'https://discord.gg/newdiscord',
        });

        moxios.stubRequest('token_update', {
            status: 200,
        });

        wrapper.vm.editDiscord();

        moxios.wait(() => {
            expect(wrapper.vm.showDiscordError).toBe(false);
            expect(wrapper.emitted().saveDiscord[0]).toEqual(['https://discord.gg/newdiscord']);
            done();
        });
    });

    it('do not save incorrect link', async () => {
        await wrapper.setProps({
            editingDiscord: true,
        });

        await wrapper.setData({
            showDiscordError: false,
            newDiscord: 'incorrect_link',
        });

        wrapper.vm.editDiscord();

        expect(wrapper.vm.showDiscordError).toBe(true);
    });

    it('show invitation text when link is not specified', async () => {
        await wrapper.setProps({
            editingDiscord: false,
        });

        expect(wrapper.findComponent('#discord-link').text()).toBe('token.discord.empty_address');
    });

    it('show link when specified', async () => {
        await wrapper.setProps({
            currentDiscord: 'https://discord.gg/newdiscord',
            editingDiscord: false,
        });

        expect(wrapper.findComponent('#discord-link').text()).toBe(wrapper.vm.currentDiscord);
    });

    describe('Verify that the "toggleEdit" event is emitted correctly', () => {
        it('When "editing" is false', async () => {
            await wrapper.setData({
                editing: false,
            });

            wrapper.vm.toggleEdit();

            expect(wrapper.vm.editing).toBe(true);
            expect(wrapper.emitted('toggleEdit')).toBeTruthy();
            expect(wrapper.emitted('toggleEdit')[0]).toEqual(['discord']);
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
