import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenDiscordChannel from '../../js/components/token/TokenDiscordChannel';
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

describe('TokenDiscordChannel', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('save correct link', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenDiscordChannel, {
            localVue,
            propsData: {
                editingDiscord: true,
                updateUrl: 'token_update',
            },
        });

        wrapper.setData({
            showDiscordError: false,
        });

        wrapper.find('input').setValue('https://discord.gg/newdiscord');
        wrapper.vm.editDiscord();

        moxios.stubRequest('token_update', {
            status: 200,
        });

        moxios.wait(() => {
            expect(wrapper.vm.showDiscordError).toBe(false);
            expect(wrapper.emitted().saveDiscord[0]).toEqual(['https://discord.gg/newdiscord']);
            done();
        });
    });

    it('do not save incorrect link', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenDiscordChannel, {
            localVue,
            data() {
              return {
                  howDiscordError: false,
              };
            },
            propsData: {
                editingDiscord: true,
            },
        });

        wrapper.find('input').setValue('incorrect_link');
        wrapper.vm.editDiscord();

        expect(wrapper.vm.showDiscordError).toBe(true);
    });

    it('show invitation text when link is not specified', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenDiscordChannel, {
            localVue,
            propsData: {
                editingDiscord: false,
            },
        });
        expect(wrapper.find('#discord-link').text()).toBe('token.discord.empty_address');
    });

    it('show link when specified', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenDiscordChannel, {
            localVue,
            propsData: {
                currentDiscord: 'https://discord.gg/newdiscord',
                editingDiscord: false,
            },
        });
        expect(wrapper.find('#discord-link').text()).toBe(wrapper.vm.currentDiscord);
    });
});
