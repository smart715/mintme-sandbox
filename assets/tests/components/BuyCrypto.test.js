import {shallowMount, createLocalVue} from '@vue/test-utils';
import BuyCrypto from '../../js/components/wallet/BuyCrypto.vue';
import axios from 'axios';
import moxios from 'moxios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
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
        coinifyUiUrl: '',
        coinifyPartnerId: 1,
        coinifyCryptoCurrencies: [],
        predefinedTokens: [],
        mintmeExchangeMailSent: false,
        viewOnly: false,
        ...props,
    };
};

const dataDepositAddressesSignature = {
    data: {
        signatures: 'signature',
        addresses: 'addresses',
    },
};

describe('BuyCrypto', () => {
    let wrapper;

    beforeEach(() => {
        moxios.install();

        wrapper = shallowMount(BuyCrypto, {
            sync: false,
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should request "send_exchange_mintme_mail" in case mail wasnt sent', async (done) => {
        await wrapper.setData({
            modalVisible: false,
            refreshToken: null,
            isExchangeMailSent: false,
        });

        moxios.stubRequest('send_exchange_mintme_mail', {
            status: 200,
            response: {},
        });

        wrapper.vm.buyCrypto();

        moxios.wait(() => {
            expect(wrapper.vm.isExchangeMailSent).toBe(true);
            expect(wrapper.vm.modalVisible).toBe(true);
            done();
        });
    });

    it('should request "deposit_addresses_signature" on buyCrypto method call', async (done) => {
        await wrapper.setData({
            modalVisible: false,
            refreshToken: null,
            isExchangeMailSent: true,
        });

        await wrapper.setProps({
            mintmeExchangeMailSent: true,
        });

        moxios.stubRequest('deposit_addresses_signature', {
            status: 200,
            response: {
                dataDepositAddressesSignature,
            },
        });

        wrapper.vm.buyCrypto();

        moxios.wait(() => {
            expect(wrapper.vm.depositAddresses).toEqual(dataDepositAddressesSignature.addresses);
            expect(wrapper.vm.addressesSignature).toEqual(dataDepositAddressesSignature.signatures);
            done();
        });
    });

    it('should update refreshToken on getRefreshToken method call with refreshToken=null', async (done) => {
        await wrapper.setData({
            refreshToken: null,
        });

        moxios.stubRequest('refresh_token', {
            status: 200,
            response: 'data',
        });

        wrapper.vm.getRefreshToken();

        moxios.wait(() => {
            expect(wrapper.vm.refreshToken).toEqual('data');
            done();
        });
    });

    it('should not update refreshToken in case refreshToken is not null', async () => {
        await wrapper.setData({
            refreshToken: 'data',
        });

        wrapper.vm.getRefreshToken();

        expect(wrapper.vm.refreshToken).toEqual('data');
    });
});
