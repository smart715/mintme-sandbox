import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import {status} from '../../js/storage/modules/websocket';
import userStore from '../../js/storage/modules/user';
import Wallet from '../../js/components/wallet/Wallet';
import Decimal from 'decimal.js';
import {webSymbol} from '../../js/utils/constants';
import moxios from 'moxios';
import axios from 'axios';

Object.defineProperty(window, 'EventSource', {
    value: jest.fn(),
});

/**
 * @param {object} params
 * @return {string}
 */
function subRouting(params) {
    return '' + (params.name ? '-' + params.name : '')
            + (params.base ? '-' + params.base : '')
            + (params.quote ? '-' + params.quote : '');
}

const $routing = {
    generate: (val, params) => {
        return val + (params ? subRouting(params) : '');
    },
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {
                retry: axios,
                single: axios,
            };
            Vue.prototype.$routing = $routing;
            Vue.prototype.$store = new Vuex.Store({
                modules: {status, user: userStore},
            });
            Vue.prototype.$toasted = {show: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

const defaultProps = {
    withdrawUrl: 'withdraw_url',
    createTokenUrl: 'createTokenUrl',
    depositMore: 'depositMore',
    twofa: 'twofa',
    websocketUrl: '',
    disabledCryptos: [],
    disabledServicesConfig: {
        depositDisabled: false,
        deployDisabled: false,
        allServicesDisabled: false,
        depositsDisabled: {},
        withdrawalsDisabled: {},
    },
    minAmount: 0.0001,
};

const assertData = {
    WEB: {name: 'WEB', available: 1, removed: false},
    bar: {name: 'bar', available: 1, removed: false},
    baz: {name: 'baz', available: 0, removed: false},
};
const expectData = [{name: 'WEB', available: 1}, {name: 'bar', available: 1}, {name: 'baz', available: 0}];
const expectedTokenData = [{name: 'WEB', available: 1}, {name: 'bar', available: 1}, {name: 'baz', available: 0}];

const assertTokens = {};
assertTokens['oTokenName'] = {};
assertTokens['oTokenName'] = {identifier: 'identifier', owner: 'owner', available: '0.5000'};

/**
 * @return {Wrapper<Vue>}
 * @param {object} extraWrapperProps
 */
function mockWallet(extraWrapperProps = {}) {
    return shallowMount(Wallet, {
        localVue: mockVue(),
        propsData: defaultProps,
        ...extraWrapperProps,
    });
}

describe('Wallet', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('computed', () => {
        it('should compute hasTokens correctly', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                tokens: null,
            });
            expect(wrapper.vm.hasTokens).toBe(false);

            await wrapper.setData({
                tokens: {foo: {name: 'foo', removed: false}},
            });
            expect(wrapper.vm.hasTokens).toBe(true);

            await wrapper.setData({
                tokens: {foo: {name: 'foo', removed: true}},
            });
            expect(wrapper.vm.hasTokens).toBe(false);

            await wrapper.setData({
                tokens: {foo: {name: 'foo', removed: true}, bar: {name: 'bar', removed: false}},
            });
            expect(wrapper.vm.hasTokens).toBe(true);
        });
        it('should hide zero balances in case of isHiddenZeroBalances equals true', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                tokens: assertData,
                isHiddenZeroBalances: true,
            });
            expect(wrapper.vm.validTokens).toEqual([
                {name: 'WEB', available: 1, removed: false},
                {name: 'bar', available: 1, removed: false},
            ]);
        });


        it('should show zero balances in case of isHiddenZeroBalances equals false', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                tokens: assertData,
                isHiddenZeroBalances: false,
            });
            expect(wrapper.vm.validTokens).toEqual([
                {name: 'WEB', available: 1, removed: false},
                {name: 'bar', available: 1, removed: false},
                {name: 'baz', available: 0, removed: false},
            ]);
        });

        it('should compute allTokens correctly', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                tokens: null,
                predefinedTokens: null,
            });

            expect(wrapper.vm.allTokens).toMatchObject({});

            await wrapper.setData({
                tokens: {foo: {name: 'foo'}},
                predefinedTokens: {bar: {name: 'bar'}},
            });
            expect(wrapper.vm.allTokens).toMatchObject({bar: {name: 'bar'}, foo: {name: 'foo'}});
        });

        it('should compute allTokensName correctly', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                tokens: {foo: {identifier: 'foo'}, bar: {identifier: 'bar'}},

            });
            expect(wrapper.vm.allTokensName).toEqual(['foo', 'bar']);
        });

        it('should compute predefinedItems correctly', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                predefinedTokens: null,
            });
            expect(wrapper.vm.predefinedItems).toEqual([]);

            await wrapper.setData({
                predefinedTokens: assertData,
            });
            expect(wrapper.vm.predefinedItems).toMatchObject(expectData);
        });

        it('should compute items correctly', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                tokens: null,
            });
            expect(wrapper.vm.items).toEqual([]);

            await wrapper.setData({
                tokens: assertData,
            });
            expect(wrapper.vm.items).toMatchObject(expectedTokenData);
        });

        it('should compute showLoadingIconP correctly', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                predefinedTokens: null,
            });

            expect(wrapper.vm.showLoadingIconP).toBe(true);

            await wrapper.setData({
                predefinedTokens: [{name: 'foo'}],
            });
            expect(wrapper.vm.showLoadingIconP).toBe(false);
        });

        it('should compute showLoadingIcon correctly', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                tokens: null,
            });
            expect(wrapper.vm.showLoadingIcon).toBe(true);

            await wrapper.setData({
                tokens: [{name: 'foo'}],
            });
            expect(wrapper.vm.showLoadingIcon).toBe(false);
        });
    });

    describe('methods', () => {
        it('should set data correctly when the function openWithdraw() is called', async (done) => {
            const predefinedTokensTest = {
                [webSymbol]: {
                    fee: '0.500000000000000000',
                    available: '.01',
                    subunit: 8,
                },
            };

            moxios.stubRequest('withdraw_delays', {
                status: 200,
                response: {
                    login: {
                        passed: true,
                        errorMsg: '',
                    },
                    registration: {
                        passed: true,
                        errorMsg: '',
                    },
                },
            });

            const wrapper = mockWallet();

            await wrapper.setProps({twofa: 'foo'});

            await wrapper.setData({
                showModal: false,
                tokens: {},
                predefinedTokens: predefinedTokensTest,
            });

            await wrapper.vm.openWithdraw(webSymbol, false, false);

            moxios.wait(() => {
                expect(wrapper.vm.showModal).toBe(true);
                expect(wrapper.vm.selectedCurrency).toBe(webSymbol);
                expect(wrapper.vm.isTokenModal).toBe(false);
                expect(wrapper.vm.currentSubunit).toBe(8);
                done();
            });
        });

        it('should set showModal correctly when the function closeWithdraw() is called', async () => {
            const wrapper = mockWallet();

            await wrapper.setData({
                showModal: true,
            });
            wrapper.vm.closeWithdraw();
            expect(wrapper.vm.showModal).toBe(false);
        });

        describe('openDeposit', () => {
            it('should set properties correctly without $axios request', async () => {
                const wrapper = mockWallet();
                const predefinedTokensTest = {
                    [webSymbol]: {
                        fee: '0.500000000000000000',
                        available: '.01',
                        subunit: 8,
                    },
                };

                await wrapper.setData({
                    tokens: {},
                    depositTokens: {},
                    predefinedTokens: predefinedTokensTest,
                });
                wrapper.vm.openDeposit(webSymbol);

                expect(wrapper.vm.selectedCurrency).toBe(webSymbol);
                expect(wrapper.vm.isTokenModal).toBe(false);
                expect(wrapper.vm.showDepositModal).toBe(true);
            });
        });

        describe('openDepositMore', () => {
            it('should set properties correctly', async () => {
                const wrapper = mockWallet();

                await wrapper.setData({
                    depositMore: webSymbol,
                    tokens: [],
                    depositTokens: [],
                    predefinedTokens: {[webSymbol]: {subunit: 8}},
                });

                wrapper.vm.openDepositMore();

                expect(wrapper.vm.selectedCurrency).toBe(webSymbol);
                expect(wrapper.vm.currentSubunit).toBe(8);
                expect(wrapper.vm.isTokenModal).toBe(false);
                expect(wrapper.vm.showDepositModal).toBe(true);
            });
        });

        describe('updateBalances', () => {
            it('should do $axios request and set properties correctly without $axios request', async () => {
                const wrapper = mockWallet();

                await wrapper.setData({
                    predefinedTokens: {['token']: {identifier: 'oTokenName'}},
                    tokens: {['token']: {identifier: 'oTokenName'}},
                });

                wrapper.vm.updateBalances(assertTokens);

                expect(wrapper.vm.predefinedTokens['token'].available).toBe('0.5000');
                expect(wrapper.vm.tokens['token'].available).toBe('0.5000');
            });

            it('should do $axios request and set properties correctly when response is not empty', async (done) => {
                const wrapper = mockWallet();

                await wrapper.setData({
                    predefinedTokens: {['token']: {identifier: 'oTokenName'}},
                    tokens: {['token']: {identifier: 'oTokenName', owner: 'owner'}},
                });

                moxios.stubRequest('lock-period-token', {
                    status: 200,
                    response: {frozenAmount: '0.05', frozenAmountWithReceived: '0.06'},
                });

                wrapper.vm.updateBalances(assertTokens);

                moxios.wait(() => {
                    expect(wrapper.vm.tokens['token'].available)
                        .toMatchObject(new Decimal(assertTokens['oTokenName'].available).sub('0.06'));
                    done();
                });
            });

            it('should do $axios request and set properties correctly when result of $axios request is empty',
                async (done) => {
                    const wrapper = mockWallet();

                    await wrapper.setData({
                        predefinedTokens: {['token']: {identifier: 'oTokenName'}},
                        tokens: {['token']: {identifier: 'oTokenName', owner: 'owner'}},
                    });

                    moxios.stubRequest('lock-period-token', {
                        status: 200,
                    });

                    wrapper.vm.updateBalances(assertTokens);

                    moxios.wait(() => {
                        expect(wrapper.vm.tokens['token'].available).toBe('0.5000');
                        done();
                    });
                });
        });

        it('should set closeDepositModal correctly when the function closeDeposit() is called', async () => {
            const wrapper = mockWallet();

            wrapper.vm.closeDepositModal();
            expect(wrapper.vm.showDepositModal).toBe(false);
        });

        it('should return correctly value when the function tokensToArray() is called', () => {
            const wrapper = mockWallet();

            expect(wrapper.vm.tokensToArray(assertData)).toMatchObject(expectData);
        });

        it('should return correctly url when the function generatePairUrl() is called', () => {
            const wrapper = mockWallet();

            expect(wrapper.vm.generatePairUrl({name: 'foo'})).toBe('token_show_trade-foo');
        });

        it('should return correctly url when the function generateCoinUrl() is called', () => {
            const wrapper = mockWallet();

            const coin = {
                name: 'foo',
                exchangeble: true,
                tradable: true,
            };

            expect(wrapper.vm.generateCoinUrl(coin)).toBe('coin-foo-WEB');

            coin.exchangeble = false;
            expect(wrapper.vm.generateCoinUrl(coin)).toBe('coin-foo-WEB');

            coin.exchangeble = true;
            coin.tradable = false;
            expect(wrapper.vm.generateCoinUrl(coin)).toBe('coin-WEB-foo');

            coin.tradable = true;
            coin.name = 'WEB';
            expect(wrapper.vm.generateCoinUrl(coin)).toBe('coin-BTC-WEB');
        });

        it('should return correctly value when the function isTokenDepositDisabled() is called', () => {
            const wrapper = mockWallet();

            const data = {
                item: {
                    blocked: false,
                },
            };

            wrapper.vm.isTokenDepositDisabled(data);
            expect(wrapper.classes('text-white')).toBe(false);

            data.item.blocked = true;
            expect(wrapper.classes('text-muted pointer-events-none')).toBe(false);
        });

        it('should return correctly value when the function isTokenWithdrawalDisabled() is called', () => {
            const wrapper = mockWallet();

            const data = {
                item: {
                    blocked: false,
                },
            };

            wrapper.vm.isTokenWithdrawalDisabled(data);
            expect(wrapper.classes('text-white')).toBe(false);

            data.item.blocked = true;
            expect(wrapper.classes('text-muted pointer-events-none')).toBe(false);
        });

        it('should return correctly value when the function tooltipRemoveTokenButton() is called', () => {
            const wrapper = mockWallet();

            const data = {
                item: {
                    available: '8.006000000000',
                    owner: false,
                    bonus: '0.000000000000',
                },
            };

            expect(wrapper.vm.tooltipRemoveTokenButton(data)).toBe('wallet.disabled.delete_token');

            data.item.owner = true;
            expect(wrapper.vm.tooltipRemoveTokenButton(data)).toBe('wallet.disabled.delete_token_owner');
        });

        it('should return correctly value when the function tooltipDepositOrWithdrawButton() is called', async () => {
            const wrapper = mockWallet();

            const data = {
                item: {
                    deployed: false,
                    cryptoSymbol: 'FOO',
                },
            };

            await wrapper.setData({
                tokens: data,
            });
            expect(wrapper.vm.tooltipDepositOrWithdrawButton(data)).toBe('wallet.disabled.deposit_and_withdraw');

            data.item.deployed = true;
            expect(wrapper.vm.tooltipDepositOrWithdrawButton(data)).toBe(wrapper.vm.tooltipDisabledDeposits(data));
        });

        it('should isTokenActionDisabled return true if isUserBlocked is true', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: {...defaultProps},
            });

            const data = {
                item: {
                    blocked: true,
                },
            };

            expect(wrapper.vm.isTokenActionDisabled(data)).toBe(true);
        });

        it('should isTokenActionDisabled be true if disabledServicesConfig.allServicesDisabled is true', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: {...defaultProps, disabledServicesConfig: {allServicesDisabled: true}},
            });

            const data = {
                item: {
                    blocked: false,
                },
            };

            expect(wrapper.vm.isTokenActionDisabled(data)).toBe(true);
        });

        describe('areCryptoTokenActionsDisabled', () => {
            const data = [
                {
                    viewOnly: true,
                    expect: true,
                },
                {
                    viewOnly: false,
                    expect: false,
                },
            ];

            describe.each(data)('should return', (testData) => {
                it(`${testData.expect}`, async () => {
                    const wrapper = mockWallet();
                    await wrapper.setProps({
                        viewOnly: testData.viewOnly,
                        isUserBlocked: testData.isUserBlocked,
                    });

                    expect(wrapper.vm.areCryptoTokenActionsDisabled()).toBe(testData.expect);
                });
            });
        });

        describe('isTokenActionDisabled', () => {
            const data = [
                {
                    areCryptoTokenActionsDisabled: true,
                    tokenBlocked: false,
                    tokenDeployed: true,
                    expect: true,
                },
                {
                    areCryptoTokenActionsDisabled: false,
                    tokenBlocked: true,
                    tokenDeployed: true,
                    expect: true,
                },
                {
                    areCryptoTokenActionsDisabled: false,
                    tokenBlocked: false,
                    tokenDeployed: false,
                    expect: true,
                },
                {
                    areCryptoTokenActionsDisabled: false,
                    tokenBlocked: false,
                    tokenDeployed: true,
                    expect: false,
                },
            ];

            describe.each(data)('should return', (testData) => {
                it(`${testData.expect}`, () => {
                    const wrapper = mockWallet();
                    jest.spyOn(wrapper.vm, 'areCryptoTokenActionsDisabled')
                        .mockReturnValue(testData.areCryptoTokenActionsDisabled);

                    const tokenData = {
                        item: {
                            blocked: testData.tokenBlocked,
                            deployed: testData.tokenDeployed,
                        },
                    };

                    expect(wrapper.vm.isTokenActionDisabled(tokenData)).toBe(testData.expect);
                });
            });
        });

        describe('isTokenDepositDisabled', () => {
            const data = [
                {
                    isTokenActionDisabled: true,
                    tokenDepositsDisabled: false,
                    expect: true,
                },
                {
                    isTokenActionDisabled: false,
                    tokenDepositsDisabled: true,
                    expect: true,
                },
                {
                    isTokenActionDisabled: false,
                    tokenDepositsDisabled: false,
                    expect: false,
                },
            ];

            describe.each(data)('should return', (testData) => {
                it(`${testData.expect}`, () => {
                    const extraWrapperProps = {
                        propsData: {
                            ...defaultProps,
                            disabledServicesConfig: {
                                tokenDepositsDisabled: testData.tokenDepositsDisabled,
                                allServicesDisabled: false,
                                depositsDisabled: {},
                                withdrawalsDisabled: {},
                            },
                        },
                    };
                    const wrapper = mockWallet(extraWrapperProps);
                    jest.spyOn(wrapper.vm, 'isTokenActionDisabled').mockReturnValue(testData.isTokenActionDisabled);

                    const data = {
                        item: {
                            blocked: false,
                            depositsDisabled: false,
                        },
                    };

                    expect(wrapper.vm.isTokenDepositDisabled(data)).toBe(testData.expect);
                });
            });
        });

        describe('isTokenWithdrawalDisabled', () => {
            const data = [
                {
                    isTokenActionDisabled: true,
                    tokenWithdrawalsDisabled: false,
                    expect: true,
                },
                {
                    isTokenActionDisabled: false,
                    tokenWithdrawalsDisabled: true,
                    expect: true,
                },
                {
                    isTokenActionDisabled: false,
                    tokenWithdrawalsDisabled: false,
                    expect: false,
                },
            ];

            describe.each(data)('should return', (testData) => {
                it(`${testData.expect}`, () => {
                    const extraWrapperProps = {
                        propsData: {
                            ...defaultProps,
                            disabledServicesConfig: {
                                tokenWithdrawalsDisabled: testData.tokenWithdrawalsDisabled,
                                allServicesDisabled: false,
                                depositsDisabled: {},
                                withdrawalsDisabled: {},
                            },
                        },
                    };
                    const wrapper = mockWallet(extraWrapperProps);
                    jest.spyOn(wrapper.vm, 'isTokenActionDisabled').mockReturnValue(testData.isTokenActionDisabled);
                    expect(wrapper.vm.isTokenWithdrawalDisabled({item: {withdrawalsDisabled: false}}))
                        .toBe(testData.expect);
                });
            });
        });

        describe('isCryptoActionDisabled', () => {
            const data = [
                {
                    areCryptoTokenActionsDisabled: true,
                    isDisabledCrypto: false,
                    expect: true,
                },
                {
                    areCryptoTokenActionsDisabled: false,
                    isDisabledCrypto: true,
                    expect: true,
                },
                {
                    areCryptoTokenActionsDisabled: false,
                    isDisabledCrypto: false,
                    expect: false,
                },
            ];

            describe.each(data)('should return', (testData) => {
                it(`${testData.expect}`, () => {
                    const wrapper = mockWallet();
                    jest.spyOn(wrapper.vm, 'areCryptoTokenActionsDisabled').mockReturnValue(testData.expect);
                    jest.spyOn(wrapper.vm, 'isDisabledCrypto').mockReturnValue(testData.isDisabledCrypto);
                    const cryptoData = {
                        name: 'foo',
                    };

                    expect(wrapper.vm.isCryptoActionDisabled(cryptoData)).toBe(testData.expect);
                });
            });
        });

        describe('isCryptoDepositDisabled', () => {
            const data = [
                {
                    isCryptoActionDisabled: true,
                    coinDepositsDisabled: false,
                    expect: true,
                },
                {
                    isCryptoActionDisabled: false,
                    coinDepositsDisabled: true,
                    expect: true,
                },
                {
                    isCryptoActionDisabled: false,
                    coinDepositsDisabled: false,
                    expect: false,
                },
            ];

            const coinData = {
                item: {
                    cryptoSymbol: 'FOO',
                },
            };

            describe.each(data)('should return', (testData) => {
                it(`${testData.expect}`, () => {
                    const extraWrapperProps = {
                        propsData: {
                            ...defaultProps,
                            disabledServicesConfig: {
                                coinDepositsDisabled: testData.coinDepositsDisabled,
                                allServicesDisabled: false,
                                depositsDisabled: {},
                                withdrawalsDisabled: {},
                            },
                        },
                    };
                    const wrapper = mockWallet(extraWrapperProps);
                    jest.spyOn(wrapper.vm, 'isCryptoActionDisabled').mockReturnValue(testData.isCryptoActionDisabled);

                    expect(wrapper.vm.isCryptoDepositDisabled(coinData)).toBe(testData.expect);
                });
            });
        });

        describe('isCryptoWithdrawalDisabled', () => {
            const data = [
                {
                    isCryptoActionDisabled: true,
                    coinWithdrawalsDisabled: false,
                    expect: true,
                },
                {
                    isCryptoActionDisabled: false,
                    coinWithdrawalsDisabled: true,
                    expect: true,
                },
                {
                    isCryptoActionDisabled: false,
                    coinWithdrawalsDisabled: false,
                    expect: false,
                },
            ];

            const coinData = {
                item: {
                    cryptoSymbol: 'FOO',
                },
            };

            describe.each(data)('should return', (testData) => {
                it(`${testData.expect}`, () => {
                    const extraWrapperProps = {
                        propsData: {
                            ...defaultProps,
                            disabledServicesConfig: {
                                coinWithdrawalsDisabled: testData.coinWithdrawalsDisabled,
                                allServicesDisabled: false,
                                depositsDisabled: {},
                                withdrawalsDisabled: {},
                            },
                        },
                    };
                    const wrapper = mockWallet(extraWrapperProps);
                    jest.spyOn(wrapper.vm, 'isCryptoActionDisabled').mockReturnValue(testData.isCryptoActionDisabled);
                    expect(wrapper.vm.isCryptoWithdrawalDisabled(coinData)).toBe(testData.expect);
                });
            });
        });


        it('should return correctly value when the function tooltipRemoveTokenButton() is called', () => {
            const wrapper = mockWallet();

            const data = {
                item: {
                    available: '8.006000000000',
                    owner: false,
                    bonus: '0.000000000000',
                },
            };

            expect(wrapper.vm.tooltipRemoveTokenButton(data)).toBe('wallet.disabled.delete_token');

            data.item.owner = true;
            expect(wrapper.vm.tooltipRemoveTokenButton(data)).toBe('wallet.disabled.delete_token_owner');
        });
    });
});
