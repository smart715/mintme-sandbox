import {mount, createLocalVue} from '@vue/test-utils';
import TokenDiscordChannel from '../../js/components/token/TokenDiscordChannel';
import moxios from 'moxios';
import axios from 'axios';

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
        const wrapper = mount(TokenDiscordChannel, {
            localVue,
            data: {
                showDiscordError: false,
            },
            propsData: {
                editingDiscord: true,
                updateUrl: 'token_update',
            },
        });

        wrapper.find('input').setValue('https://discord.gg/newdiscord');
        wrapper.vm.editDiscord();

        moxios.stubRequest('token_update', {
            status: 202,
        });

        moxios.wait(() => {
            expect(wrapper.vm.showDiscordError).toBe(false);
            expect(wrapper.emitted().saveDiscord[0]).toEqual(['https://discord.gg/newdiscord']);
            done();
        });
    });

    it('do not save incorrect link', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenDiscordChannel, {
            localVue,
            data: {
                showDiscordError: false,
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
        const wrapper = mount(TokenDiscordChannel, {
            localVue,
            propsData: {
                editingDiscord: false,
            },
        });
        expect(wrapper.find('#discord-link').text()).toBe('Add Discord invitation link');
    });

    it('show link when specified', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenDiscordChannel, {
            localVue,
            propsData: {
                currentDiscord: 'https://discord.gg/newdiscord',
                editingDiscord: false,
            },
        });
        expect(wrapper.find('#discord-link').text()).toBe(wrapper.vm.currentDiscord);
    });
});
