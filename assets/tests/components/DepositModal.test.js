import {shallowMount, createLocalVue} from '@vue/test-utils';
import DepositModal from '../../js/components/modal/DepositModal';
import Modal from '../../js/components/modal/Modal';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';

const rebrandingTest = (val) => {
    if (!val) {
        return val;
    }

    const brandDict = [
        {regexp: /(WebchainTest)/g, replacer: 'MintMe Coin Test'},
        {regexp: /(webTest)/g, replacer: 'mintimeTest'},
    ];
    brandDict.forEach((item) => {
        if ('string' !== typeof val) {
            return;
        }
        val = val.replace(item.regexp, item.replacer);
    });

    return val;
};

/**
 * @return {Component}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$te = (val) => true;
            Vue.prototype.$tc = (val) => val;
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockDepositModal(props = {}) {
    const isToken = props.isToken ?? true;

    return shallowMount(DepositModal, {
        localVue: mockVue(),
        propsData: {
            visible: true,
            currency: isToken ? 'tokTest': 'WEB',
            subunit: isToken ? 4 : 8,
            isToken,
            isCreatedOnMintmeSite: true,
            isOwner: true,
            tokenNetworks: isToken ? {
                WEB: {
                    networkInfo: {symbol: 'WEB'},
                    fee: '0.1',
                    available: '100',
                    subunit: 8,
                },
            }: null,
            cryptoNetworks: !isToken ? {
                ETH: {
                    networkInfo: {symbol: 'ETH'},
                    fee: '0.003',
                    feeCurrency: 'ETH',
                    subunit: 8,
                },
            }: null,
            noClose: false,
            ...props,
        },
        filters: {
            rebranding: (val) => rebrandingTest(val),
        },
        stubs: {
            Modal: Modal,
        },
        directives: {
            'b-tooltip': {},
        },
        store: new Vuex.Store({
            modules: {
                crypto: {
                    namespaced: true,
                    getters: {
                        getCryptosMap: () => {
                            return {
                                'BTC': {},
                                'WEB': {},
                                'ETH': {},
                            };
                        },
                    },
                },
            },
        }),
    });
}

describe('DepositModal', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Verify that "selectedNetworkName" works correctly', async () => {
        const wrapper = mockDepositModal();

        await wrapper.setData({
            selectedNetwork: {
                networkInfo: {
                    symbol: 'BTC',
                },
            },
        });

        expect(wrapper.vm.selectedNetworkName()).toBe('dynamic.blockchain_BTC_name');
    });

    it('Verify that "showHasTaxWarningMessage" works correctly', () => {
        const wrapper = mockDepositModal();

        expect(wrapper.vm.showHasTaxWarningMessage).toBe('deposit_modal.token_has_tax');
    });

    it('should be visible when visible props is true', () => {
        const wrapper = mockDepositModal();

        expect(wrapper.vm.visible).toBe(true);
    });

    it('should provide closing on ESC and closing on backdrop click when noClose props is false', () => {
        const wrapper = mockDepositModal();

        expect(wrapper.vm.noClose).toBe(false);
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = mockDepositModal();

        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('emit "success" when clicking on button "OK"', () => {
        const wrapper = mockDepositModal();

        wrapper.findComponent('button.btn.btn-primary').trigger('click');
        expect(wrapper.emitted('success').length).toBe(1);
    });

    it('emit "success" when the function onSuccess() is called', () => {
        const wrapper = mockDepositModal();

        wrapper.vm.onSuccess();
        expect(wrapper.emitted('success').length).toBe(1);
    });

    it('"supportNetworkSelector" should be true for token and WEB', () => {
        const tokenModal = mockDepositModal();
        expect(tokenModal.vm.supportNetworkSelector).toBe(true);

        const webModal = mockDepositModal({isToken: false, currency: 'WEB'});
        expect(webModal.vm.supportNetworkSelector).toBe(true);

        const ethModal = mockDepositModal({isToken: false, currency: 'ETH'});
        expect(ethModal.vm.supportNetworkSelector).toBe(true);
    });

    it('"networkObjects" should be return right object depending if its token', () => {
        const tokenModal = mockDepositModal();
        expect(tokenModal.vm.networkObjects).toMatchObject({
            WEB: {
                networkInfo: {symbol: 'WEB'},
                fee: '0.1',
                available: '100',
                subunit: 8,
            },
        });

        const webModal = mockDepositModal({isToken: false}); // WEB
        expect(webModal.vm.networkObjects).toMatchObject({
            ETH: {
                networkInfo: {symbol: 'ETH'},
                fee: '0.003',
                feeCurrency: 'ETH',
                subunit: 8,
            },
        });
    });

    it('should be "0xTEST" address when it network is selected and its token', async () => {
        const wrapper = mockDepositModal();

        await wrapper.setData({
            addresses: {
                'WEB': '0xTEST',
                'ETH': '0xTESTETH',
            },
            selectedNetwork: {networkInfo: {symbol: 'WEB'}},
        });

        expect(wrapper.html().includes('0xTEST')).toBe(true);
    });

    it('should be "0xTESTETH" address when it network is selected and its token', async () => {
        const wrapper = mockDepositModal({isToken: false});

        await wrapper.setData({
            addresses: {
                'WEB': '0xTEST',
                'ETH': '0xTESTETH',
            },
            selectedNetwork: {networkInfo: {symbol: 'ETH'}},
        });

        expect(wrapper.html().includes('0xTESTETH')).toBe(true);
    });

    it('should be contain the description', () => {
        const wrapper = mockDepositModal();

        expect(wrapper.html().includes('wallet.send_to_address')).toBe(true);
    });

    it('should contain right value in minDeposit and fee field', (done) => {
        const wrapper = mockDepositModal();

        moxios.stubRequest('deposit_info', {
            status: 200,
            response: {
                minDeposit: '0.1',
                fee: '2',
            },
        });

        wrapper.vm.getDepositInfo('WEB');

        moxios.wait(() => {
            const html = wrapper.html();

            expect(wrapper.vm.fee).toBe('2');
            expect(html.includes('tokTest')).toBe(true);

            expect(wrapper.vm.minDeposit).toBe('0.1');
            expect(html.includes('tokTest')).toBe(true);

            done();
        });
    });
});
