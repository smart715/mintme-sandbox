import {createLocalVue, shallowMount} from '@vue/test-utils';
import Donation from '../../js/components/donation/Donation';
import moxios from 'moxios';
import axios from 'axios';
import {webSymbol, btcSymbol, tokSymbol, MINTME} from '../../js/utils/constants';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.component('font-awesome-icon', {});
    localVue.component('b-dropdown', {});
    localVue.component('b-dropdown-item', {});
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
        },
    });

    return localVue;
}

describe('Donation', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should renders correctly for logged in user', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
            },
            localVue: mockVue(),
        });

        expect(wrapper.vm.dropdownText).toBe('Select currency');
        expect(wrapper.vm.isCurrencySelected).toBe(false);
        expect(wrapper.vm.loginFormLoaded).toBe(true);
        expect(wrapper.vm.buttonDisabled).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.find('.donation-header span').text()).toBe('Donations');
        expect(wrapper.find('b-dropdown-stub').exists()).toBe(true);
    });

    it('should renders correctly for not logged in user', () => {
        const wrapper = shallowMount(Donation, {
            localVue: mockVue(),
            propsData: {
                loggedIn: false,
            },
        });

        expect(wrapper.vm.loginFormLoaded).toBe(false);
        expect(wrapper.vm.buttonDisabled).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.vm.dropdownText).toBe('Select currency');
        expect(wrapper.find('.donation-header span').text()).toBe('To make a donation you have to be logged in');
        expect(wrapper.find('b-dropdown-stub').exists()).toBe(false);
    });

    it('should renders correctly for logged in user and load balance for selected currency', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
                donationParams: {fee: .01},
                market: {quote: 'tok1'},
            },
            localVue: mockVue(),
        });

        expect(wrapper.vm.dropdownText).toBe('Select currency');
        expect(wrapper.vm.isCurrencySelected).toBe(false);
        expect(wrapper.vm.buttonDisabled).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.find('.donation-header span').text()).toBe('Donations');
        expect(wrapper.find('b-dropdown-stub').exists()).toBe(true);

        // Select WEB
        wrapper.vm.onSelect(webSymbol);
        expect(wrapper.vm.isCurrencySelected).toBe(true);
        expect(wrapper.vm.balanceLoaded).toBe(false);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.vm.selectedCurrency).toBe(webSymbol);

        // Select BTC
        wrapper.vm.onSelect(btcSymbol);
        expect(wrapper.vm.isCurrencySelected).toBe(true);
        expect(wrapper.vm.balanceLoaded).toBe(false);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.vm.selectedCurrency).toBe(btcSymbol);
    });

    it('should rebrand selected currency WEB -> MINTME', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
                donationParams: {fee: .01},
                market: {quote: 'tok1'},
            },
            localVue: mockVue(),
        });

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.donationCurrency).toBe(MINTME.symbol);

        wrapper.vm.selectedCurrency = btcSymbol;
        expect(wrapper.vm.donationCurrency).toBe(btcSymbol);

        wrapper.vm.selectedCurrency = tokSymbol;
        expect(wrapper.vm.donationCurrency).toBe(tokSymbol);
    });

    it('should generate link to wallet for selected currency', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: true,
                donationParams: {fee: .01},
                market: {quote: 'tok1'},
            },
        });

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.getDepositLink).toBe('wallet');

        wrapper.vm.selectedCurrency = btcSymbol;
        expect(wrapper.vm.getDepositLink).toBe('wallet');
    });

    it('should properly check if currency selected', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
                donationParams: {fee: .01},
                market: {quote: 'tok1'},
            },
            localVue: mockVue(),
        });

        wrapper.vm.selectedCurrency = '';
        expect(wrapper.vm.isCurrencySelected).toBe(false);

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.isCurrencySelected).toBe(true);

        wrapper.vm.selectedCurrency = tokSymbol;
        expect(wrapper.vm.isCurrencySelected).toBe(false);

        wrapper.vm.selectedCurrency = btcSymbol;
        expect(wrapper.vm.isCurrencySelected).toBe(true);
    });

    it('should properly check insufficient funds', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
                donationParams: {fee: .01},
                market: {
                    quote: 'tok1',
                    base: {
                        subunit: 4,
                    },
                },
            },
            localVue: mockVue(),
        });

        wrapper.vm.balanceLoaded = false;
        expect(wrapper.vm.insufficientFunds).toBe(false);

        wrapper.vm.balanceLoaded = true;
        expect(wrapper.vm.insufficientFunds).toBe(true);

        wrapper.vm.balance = '0.5';
        expect(wrapper.vm.insufficientFunds).toBe(false);

        wrapper.vm.amountToDonate = '0.55';
        expect(wrapper.vm.insufficientFunds).toBe(true);
    });

    it('should properly check amount to donate', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
                donationParams: {
                    fee: 1,
                    minBtcAmount: 0.000001,
                    minMintmeAmount: 0.0001,
                },
                market: {quote: 'tok1'},
            },
            localVue: mockVue(),
        });

        wrapper.vm.amountToDonate = '';
        expect(wrapper.vm.isAmountValid).toBe(false);

        wrapper.vm.amountToDonate = '0.0';
        expect(wrapper.vm.isAmountValid).toBe(false);

        wrapper.vm.amountToDonate = '0.001';
        expect(wrapper.vm.isAmountValid).toBe(true);

        wrapper.vm.amountToDonate = '0.0000';
        expect(wrapper.vm.isAmountValid).toBe(false);

        wrapper.vm.amountToDonate = '0.0001';
        expect(wrapper.vm.isAmountValid).toBe(true);

        wrapper.vm.selectedCurrency = btcSymbol;
        wrapper.vm.amountToDonate = 0.0000001;
        expect(wrapper.vm.isAmountValid).toBe(false);
        wrapper.vm.amountToDonate = 0.000001;
        expect(wrapper.vm.isAmountValid).toBe(true);

        wrapper.vm.selectedCurrency = webSymbol;
        wrapper.vm.amountToDonate = 0.00001;
        expect(wrapper.vm.isAmountValid).toBe(false);
        wrapper.vm.amountToDonate = 0.0001;
        expect(wrapper.vm.isAmountValid).toBe(true);
    });

    it('should properly disable button', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: false,
                market: {
                    quote: 'tok1',
                    base: {
                        subunit: 4,
                    },
                },
                donationParams: {
                    fee: .01,
                    minMintmeAmount: 0.0001,
                },
            },
        });

        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.setProps({loggedIn: true});
        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.vm.balanceLoaded = true;
        wrapper.vm.balance = '20';
        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.vm.amountToDonate = '5';
        expect(wrapper.vm.buttonDisabled).toBe(false);

        wrapper.vm.donationChecking = true;
        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.vm.donationChecking = false;
        wrapper.vm.donationInProgress = true;
        expect(wrapper.vm.buttonDisabled).toBe(true);
    });

    it('can select currency', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
                donationParams: {fee: .01},
                market: {quote: 'tok1'},
            },
            localVue: mockVue(),
        });

        wrapper.vm.balanceLoaded = true;
        wrapper.vm.onSelect(webSymbol);
        expect(wrapper.vm.selectedCurrency).toBe(webSymbol);
        expect(wrapper.vm.balanceLoaded).toBe(false);

        wrapper.vm.balanceLoaded = true;
        wrapper.vm.onSelect(btcSymbol);
        expect(wrapper.vm.selectedCurrency).toBe(btcSymbol);
        expect(wrapper.vm.balanceLoaded).toBe(false);
    });

    it('can load login form', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: false,
                donationParams: {fee: .01},
                market: {quote: 'tok1'},
            },
        });

        moxios.stubRequest('login', {
            status: 200,
            response: '<form></form>',
        });

        wrapper.vm.loadLoginForm();

        moxios.wait(() => {
            expect(wrapper.vm.loginFormLoaded).toBe(true);
            expect(wrapper.html()).toContain('<form>');
            done();
        });
    });

    it('can load token balance', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: true,
                donationParams: {
                    minMintmeAmount: .0001,
                    fee: .01,
                },
                market: {
                    quote: 'tok1',
                },
            },
        });

        wrapper.vm.selectedCurrency = webSymbol;
        wrapper.vm.getTokenBalance();

        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            request.respondWith({
                status: 200,
                response: 111,
            }).then(() => {
                expect(wrapper.vm.balance).toBe(111);
                expect(wrapper.vm.balanceLoaded).toBe(true);
                done();
            });
        });
    });

    it('can check donation if logged in and currency selected and amount to donate not null', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: true,
                market: {
                    base: {
                        symbol: webSymbol,
                    },
                    quote: {
                        symbol: 'TOK00011122233',
                    },
                },
                donationParams: {
                    minBtcAmount: '0.000001',
                    minMintmeAmount: '0.0001',
                },
            },
        });

        moxios.stubRequest('check_donation', {
            status: 202,
            response: {
                amountToReceive: '2.5674',
            },
        });

        wrapper.vm.amountToDonate = 50;
        wrapper.vm.checkDonation();
        expect(wrapper.vm.donationChecking).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.amountToReceive).toBe('2.5674');
            expect(wrapper.vm.donationChecking).toBe(false);
            done();
        });
    });

    it('can make donation if logged in and currency selected and amount to donate/receive not null', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: true,
                donationParams: {
                    fee: .01,
                },
                market: {
                    base: {
                        symbol: webSymbol,
                    },
                    quote: {
                        symbol: 'TOK00011122233',
                    },
                },
            },
        });

        wrapper.setData({
            selectedCurrency: webSymbol,
            amountToDonate: 20,
            amountToReceive: 2,
            balance: 220,
        });

        moxios.stubRequest('make_donation', {
            status: 202,
        });

        wrapper.vm.makeDonation();
        expect(wrapper.vm.donationInProgress).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.amountToDonate).toBe(0);
            expect(wrapper.vm.amountToReceive).toBe(0);
            expect(wrapper.vm.balanceLoaded).toBe(false);
            wrapper.vm.$nextTick(() => {
                expect(wrapper.vm.donationInProgress).toBe(false);
                done();
            });
        });
    });

    it('can use all funds to donate', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: true,
                market: {
                    base: {
                        subunit: 4,
                    },
                    quote: {
                        symbol: 'TOK3333322221111',
                    },
                },
                donationParams: {
                    minBtcAmount: '0.000001',
                    minMintmeAmount: '0.0001',
                },
            },
        });

        wrapper.vm.selectedCurrency = webSymbol;
        wrapper.vm.balance = 100;
        wrapper.vm.amountToDonate = 20;
        wrapper.vm.all();

        expect(wrapper.vm.amountToDonate).toBe('100');

        // Insufficient funds
        wrapper.vm.balance = 100;
        wrapper.vm.amountToDonate = 120;
        wrapper.vm.all();

        expect(wrapper.vm.amountToDonate).toBe('100');
    });

    it('should reset amount to donate and amount to receive on calling resetAmount()', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
                market: {
                    quote: 'tok1',
                },
            },
            localVue: mockVue(),
        });

        wrapper.vm.amountToDonate = 50;
        wrapper.vm.amountToReceive = 7;
        wrapper.vm.resetAmount();

        expect(wrapper.vm.amountToDonate).toBe(0);
        expect(wrapper.vm.amountToReceive).toBe(0);
    });
});
