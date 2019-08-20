import {mount, createLocalVue} from '@vue/test-utils';
import TokenTelegramChannel from '../../js/components/token/TokenTelegramChannel';
import moxios from 'moxios';
import axios from 'axios';

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

describe('TokenTelegramChannel', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('save correct link', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenTelegramChannel, {
            localVue,
            data: {
                showTelegramError: false,
            },
            propsData: {
                editingTelegram: true,
                updateUrl: 'token_update',
            },
        });

        wrapper.find('input').setValue('https://t.me/joinchat/newtelegram');
        wrapper.vm.checkTelegramUrl();

        moxios.stubRequest('token_update', {
            status: 202,
        });

        moxios.wait(() => {
            expect(wrapper.vm.showTelegramError).to.equal(false);
            expect(wrapper.emitted().saveTelegram[0]).to.deep.equal(['https://t.me/joinchat/newtelegram']);
            done();
        });
    });

    it('do not save incorrect link', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenTelegramChannel, {
            localVue,
            data: {
                showTelegramError: false,
            },
            propsData: {
                editingTelegram: true,
            },
        });

        wrapper.find('input').setValue('incorrect_link');
        wrapper.vm.checkTelegramUrl();

        expect(wrapper.vm.showTelegramError).to.equal(true);
    });

    it('show invitation text when link is not specified', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenTelegramChannel, {
            localVue,
            propsData: {
                editingTelegram: false,
            },
        });
        expect(wrapper.find('#telegram-link').text()).to.equal('Add Telegram invitation link');
    });

    it('show link when specified', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenTelegramChannel, {
            localVue,
            propsData: {
                currentTelegram: 'https://t.me/joinchat/newtelegram',
                editingTelegram: false,
            },
        });
        expect(wrapper.find('#telegram-link').text()).to.equal(wrapper.vm.currentTelegram);
    });

    it('show truncated link when and too long', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenTelegramChannel, {
            localVue,
            propsData: {
                currentTelegram: 'https://t.me/joinchat/newtelegram'.padEnd(100, '0'),
                editingTelegram: false,
            },
        });
        expect(wrapper.find('#telegram-link').text()).to.equal('https://t.me/joinchat/newtelegram00..');
    });
});
