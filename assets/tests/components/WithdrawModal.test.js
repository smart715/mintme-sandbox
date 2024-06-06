import Vuelidate from 'vuelidate';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import WithdrawModal from '../../js/components/modal/WithdrawModal';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuelidate);
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$te = (val) => true;
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

const rebrandingTest = (val) => {
    if (!val) {
        return val;
    }

    const brandDict = [
        {regexp: /(web)/g, replacer: 'mintme'},
    ];

    brandDict.forEach((item) => {
        if ('string' !== typeof val) {
            return;
        }
        val = val.replace(item.regexp, item.replacer);
    });

    return val;
};

const defaultProps = {
    visible: true,
    currency: 'Token',
    isToken: true,
    isCreatedOnMintmeSite: true,
    isOwner: true,
    tokenNetworks: {
        WEB: {
            symbol: 'WEB',
            fee: '10',
            feeCurrency: 'WEB',
            subunit: 4,
        },
    },
    availableBalances: {
        ETH: '100',
        WEB: '100',
        Token: '100',
    },
    withdrawUrl: 'withdraw_url',
    subunit: 4,
    twofa: '0',
    noClose: false,
    isHackerAllowed: false,
    minWithdrawal: {
        BTC: 0.0005,
        ETH: 0.005,
        BNB: 0.05,
        USDC: 10,
        CRO: 50,
        WEB: 10,
    },
};

/**
 * @param {object} props
 * @return {Wrapper<Vue>}
 */
function mountWithdrawModal(props = {}) {
    return shallowMount(WithdrawModal, {
        propsData: {...defaultProps, ...props},
        localVue: mockVue(),
        stubs: {
            Modal: {template: '<div><slot name="body"></slot></div>'},
        },
        filters: {
            rebranding: function(val) {
                return rebrandingTest(val);
            },
        },
        store: new Vuex.Store({
            modules: {
                crypto: {
                    namespaced: true,
                    getters: {
                        getCryptosMap: () => {
                            return {
                                'BTC': {networkInfo: {blockchainAvailable: true}},
                                'WEB': {networkInfo: {blockchainAvailable: true}},
                                'ETH': {networkInfo: {blockchainAvailable: true}},
                            };
                        },
                    },
                },
            },
        }),
    });
}

describe('WithdrawModal', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    const addressBTCOk = '1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2';
    const amountOk = 100;
    const subunitOk = 0;
    const maxAmountOk = '1000';
    const code = '123456';
    it('should be visible when visible props is true', () => {
        const wrapper = mountWithdrawModal();
        expect(wrapper.vm.visible).toBe(true);
    });

    it('should provide closing on ESC and closing on backdrop click when noClose props is false', () => {
        const wrapper = mountWithdrawModal();
        expect(wrapper.vm.noClose).toBe(false);
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = mountWithdrawModal();
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('emit "cancel" and "close" when clicking on button "Cancel"', () => {
        const wrapper = mountWithdrawModal();
        wrapper.findComponent('button.btn-cancel.pl-3.c-pointer').trigger('click');
        expect(wrapper.emitted('cancel').length).toBe(1);
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('should be equal "0.001" when subunit props is equal 3 ' +
        'and minWithdrawal does not contain value for currency property', async () => {
        const wrapper = mountWithdrawModal();

        await wrapper.setProps({
            subunit: 3,
        });
        expect(wrapper.vm.minAmount).toBe('0.001');
    });

    it('min amount for crypto should be taken from minWithdrawal object', async () => {
        const wrapper = mountWithdrawModal();

        await wrapper.setProps({
            currency: 'WEB',
        });
        expect(wrapper.vm.minAmount).toBe('10');
    });

    it('should contain "123456789" in the "Withdrawal fee" field  when network is selected', async () => {
        const webNetwork = {
            symbol: 'WEB',
            fee: '123456789',
            feeCurrency: 'WEB',
            subunit: 4,
        };
        const wrapper = mountWithdrawModal({
            tokenNetworks: {
                WEB: webNetwork,
            },
        });

        await wrapper.setData({
            selectedNetwork: webNetwork,
        });

        expect(wrapper.html().includes('123456789')).toBe(true);
    });

    it('feeCurrency, and feeAmount should change depending on network', async () => {
        const webNetwork = {
            symbol: 'WEB',
            fee: '1234',
            feeCurrency: 'WEB',
            subunit: 4,
        };
        const ethNetwork = {
            symbol: 'ETH',
            fee: '4321',
            feeCurrency: 'ETH',
            subunit: 4,
        };

        const wrapper = mountWithdrawModal({
            tokenNetworks: {
                WEB: webNetwork,
                ETH: ethNetwork,
            }});

        await wrapper.setData({
            selectedNetwork: webNetwork,
        });

        expect(wrapper.vm.feeCurrency).toBe('WEB');
        expect(wrapper.vm.feeAmount).toBe('1234');

        await wrapper.setData({
            selectedNetwork: ethNetwork,
        });

        expect(wrapper.vm.feeCurrency).toBe('ETH');
        expect(wrapper.vm.feeAmount).toBe('4321');
    });

    it('should be contain "mintimeTest" in the form', () => {
        const wrapper = mountWithdrawModal({isToken: false, currency: 'webTest'});
        expect(wrapper.html().includes('mintmeTest')).toBe(true);
    });

    it('fullAmount should be the sum of fee and amount if both currencies are the same', async () => {
        const webNetwork = {
            symbol: 'WEB',
            fee: '3567',
            feeCurrency: 'Token', // same as this.currency
            subunit: 4,
        };
        const wrapper = mountWithdrawModal({
            subunit: 2,
            tokenNetworks: {
                WEB: webNetwork,
            },
        });

        await wrapper.setData({
            selectedNetwork: webNetwork,
            amount: 12345.1234,
        });

        expect(wrapper.vm.fullAmount).toBe('15912.12');
    });

    it('fullAmount should be amount only if both currencies are not the same', async () => {
        const webNetwork = {
            symbol: 'WEB',
            fee: '3567',
            feeCurrency: 'WEB', // not the same as this.currency
            subunit: 4,
        };

        const wrapper = mountWithdrawModal({
            subunit: 2,
            tokenNetworks: {
                WEB: webNetwork,
            },
        });

        await wrapper.setData({
            selectedNetwork: webNetwork,
            amount: 2456.789,
        });

        expect(wrapper.vm.fullAmount).toBe('2456.78');
    });

    it('should\'t be equal "12345f" in the "Total to be withdrawn" field', async () => {
        const wrapper = mountWithdrawModal({});

        await wrapper.setData({
            amount: '12345f',
        });
        expect(wrapper.vm.fullAmount).toBe('0');
    });

    it('shouldn\'t be error when address data is correct', async () => {
        const webNetwork = {
            symbol: 'WEB',
            fee: '3567',
            feeCurrency: 'WEB',
            subunit: 4,
        };

        const wrapper = mountWithdrawModal({currency: 'BTC'});

        await wrapper.setData({
            selectedNetwork: webNetwork,
            address: addressBTCOk,
        });

        wrapper.vm.$v.$touch();

        expect(wrapper.vm.$v.address.$error).toBe(false);
    });

    it('should be error when amount data is incorrect', async () => {
        const webNetwork = {
            symbol: 'WEB',
            fee: '3567',
            feeCurrency: 'WEB',
            subunit: 4,
        };

        const wrapper = mountWithdrawModal({});

        await wrapper.setData({selectedNetwork: webNetwork});

        await wrapper.setData({amount: ''});
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.amount.$error).toBe(true);

        await wrapper.setData({amount: 'abcd'});
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.amount.$error).toBe(true);

        await wrapper.setData({amount: '0.1'});
        await wrapper.setProps({subunit: 0});
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.amount.$error).toBe(true);

        await wrapper.setProps({availableBalances: {
            Token: maxAmountOk,
            WEB: 0,
        }});

        await wrapper.setData({amount: maxAmountOk + '1000'});
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.amount.$error).toBe(true);
    });

    it('shouldn\'t be error when amount data is correct', async () => {
        const webNetwork = {
            symbol: 'WEB',
            fee: '3567',
            feeCurrency: 'WEB',
            subunit: 4,
        };

        const wrapper = mountWithdrawModal({
            subunit: subunitOk,
            availableBalances: {
                Token: maxAmountOk,
                WEB: maxAmountOk,
            },
        });

        await wrapper.setData({
            selectedNetwork: webNetwork,
            amount: amountOk,
        });
        wrapper.vm.$v.$touch();

        expect(wrapper.vm.$v.amount.$error).toBe(false);
    });

    it('calculate the amount correctly when the function setMaxAmount() is called', async () => {
        const webNetwork = {
            symbol: 'WEB',
            fee: '123.1234',
            feeCurrency: 'Token',
            subunit: 2,
        };

        const wrapper = mountWithdrawModal({
            subunit: 2,
            availableBalances: {
                Token: maxAmountOk,
            },
            tokenNetworks: {
                WEB: webNetwork,
            },
        });

        await wrapper.setData({
            selectedNetwork: webNetwork,
            amount: amountOk,
        });

        wrapper.vm.setMaxAmount();
        expect(wrapper.vm.amount).toBe('876.88'); // 1000 - 123.1234 rounded up to 2 decimals

        await wrapper.setProps({
            availableBalances: {
                Token: '100',
            },
        });

        wrapper.vm.setMaxAmount();

        expect(wrapper.vm.amount).toBe('0');
    });

    it('do $axios request and emit "withdraw" when the function onWithdraw() is called', async (done) => {
        const btcNetwork = {
            symbol: 'BTC',
            fee: '1',
            feeCurrency: 'BTC',
            subunit: subunitOk,
        };

        const wrapper = mountWithdrawModal({
            subunit: subunitOk,
            currency: 'BTC',
            availableBalances: {
                BTC: maxAmountOk,
            },
            isToken: false,
            cryptoNetworks: {
                BTC: {
                    symbol: 'BTC',
                    fee: '1',
                    feeCurrency: 'BTC',
                    subunit: subunitOk,
                },
            },
        });

        moxios.stubRequest('withdraw_url', {
            status: 200,
        });

        await wrapper.setData({
            selectedNetwork: btcNetwork,
            address: addressBTCOk,
            amount: amountOk,
            code: code, // withdraw emitted here
            withdrawing: false,
        });

        wrapper.vm.$v.$touch();

        wrapper.vm.onWithdraw(); // and here

        expect(wrapper.emitted('withdraw').length).toBe(2);


        moxios.wait(() => {
            expect(wrapper.vm.withdrawing).toBe(false);
            done();
        });
    });

    describe('isInsufficientAmount', () => {
        it('should return false if has decimal validation error', async () => {
            const webNetwork = {
                symbol: 'WEB',
                fee: '123.1234',
                feeCurrency: 'WEB',
                subunit: 2,
            };

            const wrapper = mountWithdrawModal({
                subunit: 2,
                availableBalances: {
                    WEB: '100',
                },
                tokenNetworks: {
                    WEB: webNetwork,
                },
            });
            await wrapper.setData({
                selectedNetwork: webNetwork,
                amount: 'abcd',
            });

            wrapper.vm.$v.$touch();

            expect(wrapper.vm.isInsufficientAmount).toBe(false);
        });

        it('should return false if does not have max validation error', async () => {
            const webNetwork = {
                symbol: 'WEB',
                fee: '123.1234',
                feeCurrency: 'WEB',
                subunit: 2,
            };

            const wrapper = mountWithdrawModal({
                currency: 'WEB',
                subunit: 2,
                availableBalances: {
                    WEB: '1000',
                },
                tokenNetworks: {
                    WEB: webNetwork,
                },
            });

            await wrapper.setData({
                selectedNetwork: webNetwork,
                amount: 200,
            });

            wrapper.vm.$v.$touch();

            expect(wrapper.vm.isInsufficientAmount).toBe(false);
        });

        it('should return true if has max validation error', async () => {
            const webNetwork = {
                symbol: 'WEB',
                fee: '123.1234',
                feeCurrency: 'WEB',
                subunit: 2,
            };

            const wrapper = mountWithdrawModal({
                currency: 'WEB',
                subunit: 2,
                availableBalances: {
                    WEB: '100',
                },
                tokenNetworks: {
                    WEB: webNetwork,
                },
            });
            await wrapper.setData({
                selectedNetwork: webNetwork,
                amount: 100,
            });

            wrapper.vm.$v.$touch();
            expect(wrapper.vm.isInsufficientAmount).toBe(true);
        });
    });

    describe('isInsufficientFee', () => {
        it('should return false if currency and feeCurrency are the same', async () => {
            const webNetwork = {
                symbol: 'WEB',
                fee: '100',
                feeCurrency: 'Token',
                subunit: 0,
            };

            const wrapper = mountWithdrawModal({
                subunit: 2,
                isToken: true,
                availableBalances: {
                    WEB: '100',
                    Token: '100',
                },
                tokenNetworks: {
                    WEB: webNetwork,
                },
            });

            await wrapper.setData({
                selectedNetwork: webNetwork,
            });

            expect(wrapper.vm.isInsufficientFee).toBe(false);
        });

        it('should return false if selected network available is more than network fee', async () => {
            const webNetwork = {
                symbol: 'WEB',
                fee: '100',
                feeCurrency: 'WEB',
                subunit: 0,
            };

            const wrapper = mountWithdrawModal({
                subunit: 2,
                isToken: true,
                availableBalances: {
                    WEB: '100',
                },
                tokenNetworks: {
                    WEB: webNetwork,
                },
            });

            await wrapper.setData({
                selectedNetwork: webNetwork,
            });

            expect(wrapper.vm.isInsufficientFee).toBe(false);
        });

        it('should return true if selected network available is less than network fee', async () => {
            const webNetwork = {
                symbol: 'WEB',
                fee: '100',
                feeCurrency: 'WEB',
                subunit: 0,
            };

            const wrapper = mountWithdrawModal({
                subunit: 2,
                isToken: true,
                availableBalances: {
                    WEB: '10',
                },
                tokenNetworks: {
                    WEB: webNetwork,
                },
            });

            await wrapper.setData({
                selectedNetwork: webNetwork,
            });

            expect(wrapper.vm.isInsufficientFee).toBe(true);
        });
    });
});
