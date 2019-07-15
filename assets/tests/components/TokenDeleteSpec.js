import {createLocalVue, mount} from '@vue/test-utils';
import TokenDelete from '../../js/components/token/TokenDelete';
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

describe('TokenDelete', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('can not be deleted if exchanged', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenDelete, {
            localVue,
            propsData: {
                name: 'foo',
                sendCodeUrl: 'sendCodeUrl',
                deleteUrl: 'deleteUrl',
                twofaEnabled: 'false',
            },
        });

        moxios.stubRequest('is_token_exchanged', {
            status: 200,
            response: true,
        });

        moxios.wait(() => {
            const img = wrapper.find('img');
            // src attribute is contain "x-icon-grey" or "x-icon"?
            const grey = img ? (img.attributes().src.indexOf('grey') !== -1) : false;
            expect(grey).to.equal(true);

            done();
        });
    });

    it('can be deleted if not exchanged', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenDelete, {
            localVue,
            propsData: {
                name: 'foo',
                sendCodeUrl: 'sendCodeUrl',
                deleteUrl: 'deleteUrl',
                twofaEnabled: 'false',
            },
        });

        moxios.stubRequest('is_token_exchanged', {
            status: 200,
            response: null,
        });

        moxios.wait(() => {
            const img = wrapper.find('img');
            const grey = img ? (img.attributes().src.indexOf('grey') !== -1) : false;
            expect(grey).to.equal(false);

            done();
        });
    });

    it('can be deleted if not exchanged', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenDelete, {
            localVue,
            propsData: {
                name: 'foo',
                sendCodeUrl: 'sendCodeUrl',
                deleteUrl: 'deleteUrl',
                twofaEnabled: 'false',
            },
        });

        moxios.stubRequest('is_token_exchanged', {
            status: 200,
            response: false,
        });

        moxios.wait(() => {
            const img = wrapper.find('img');
            const grey = img ? (img.attributes().src.indexOf('grey') !== -1) : false;
            expect(grey).to.equal(false);

            done();
        });
    });

    it('do not need to send auth code when 2fa enabled', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenDelete, {
            localVue,
            propsData: {
                name: 'foo',
                sendCodeUrl: 'sendCodeUrl',
                deleteUrl: 'deleteUrl',
                twofaEnabled: 'true',
            },
        });

        expect(wrapper.vm.needToSendCode).to.equal(false);
        done();
    });

    it('need to send auth code whe 2fa disabled', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenDelete, {
            localVue,
            propsData: {
                name: 'foo',
                sendCodeUrl: 'sendCodeUrl',
                deleteUrl: 'deleteUrl',
                twofaEnabled: 'false',
            },
        });

        expect(wrapper.vm.needToSendCode).to.equal(true);
        done();
    });

    it('do not need send auth code when it already sent', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenDelete, {
            localVue,
            propsData: {
                name: 'foo',
                sendCodeUrl: 'sendCodeUrl',
                deleteUrl: 'deleteUrl',
                twofaEnabled: 'false',
            },
        });

        moxios.stubRequest('token_send_code', {
            status: 200,
            message: 'message',
        });

        moxios.wait(() => {
            expect(wrapper.vm.needToSendCode).to.equal(true);
            done();
        });

        done();
    });
});
