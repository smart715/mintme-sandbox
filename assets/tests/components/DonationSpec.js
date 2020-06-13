import {createLocalVue, mount, shallowMount} from '@vue/test-utils';
import Donation from '../../js/components/donation/Donation';
import moxios from 'moxios';
import axios from 'axios';
import {webSymbol, btcSymbol, tokSymbol, MINTME} from '../../js/utils/constants';

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

describe('Donation', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should renders correctly for logged in user', () => {
        const wrapper = mount(Donation, {
            propsData: {
                loggedIn: true,
            },
        });

        expect(wrapper.vm.dropdownText).to.equal('Select currency');
        expect(wrapper.vm.isCurrencySelected).to.be.false;
        expect(wrapper.vm.loginFormLoaded).to.be.true;
        expect(wrapper.vm.buttonDisabled).to.be.true;
        expect(wrapper.vm.isAmountValid).to.be.false;
        expect(wrapper.find('.donation-header span').text()).to.equal('Donations');
        expect(wrapper.find('b-dropdown').exists()).to.deep.equal(true);
    });

    it('should renders correctly for not logged in user', () => {
        const localVue = mockVue();
        const wrapper = mount(Donation, {
            localVue,
            propsData: {
                loggedIn: false,
            },
        });

        expect(wrapper.vm.loginFormLoaded).to.be.false;
        expect(wrapper.vm.buttonDisabled).to.be.true;
        expect(wrapper.vm.isAmountValid).to.be.false;
        expect(wrapper.vm.dropdownText).to.equal('Select currency');
        expect(wrapper.find('.donation-header span').text()).to.equal('To make a donation you have to be logged in');
        expect(wrapper.find('b-dropdown').exists()).to.deep.equal(false);
    });

    it('should renders correctly for logged in user and load balance for selected currency', () => {
        const wrapper = mount(Donation, {
            propsData: {
                loggedIn: true,
            },
        });

        expect(wrapper.vm.dropdownText).to.equal('Select currency');
        expect(wrapper.vm.isCurrencySelected).to.be.false;
        expect(wrapper.vm.buttonDisabled).to.be.true;
        expect(wrapper.vm.isAmountValid).to.be.false;
        expect(wrapper.find('.donation-header span').text()).to.equal('Donations');
        expect(wrapper.find('b-dropdown').exists()).to.deep.equal(true);

        wrapper.find('b-dropdown').trigger('click');
        expect(wrapper.find('b-dropdown-item').exists()).to.deep.equal(true);
        // Select WEB
        wrapper.find('b-dropdown-item:first-child').trigger('click');
        expect(wrapper.vm.isCurrencySelected).to.be.true;
        expect(wrapper.vm.balanceLoaded).to.be.false;
        expect(wrapper.vm.isAmountValid).to.be.false;
        expect(wrapper.vm.selectedCurrency).to.be.equal(webSymbol);

        // Select BTC
        wrapper.find('b-dropdown-item:last-child').trigger('click');
        expect(wrapper.vm.isCurrencySelected).to.be.true;
        expect(wrapper.vm.balanceLoaded).to.be.false;
        expect(wrapper.vm.isAmountValid).to.be.false;
        expect(wrapper.vm.selectedCurrency).to.be.equal(btcSymbol);
    });

    it('should rebrand selected currency WEB -> MINTME', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
            },
        });

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.donationCurrency).to.be.equal(MINTME.symbol);

        wrapper.vm.selectedCurrency = btcSymbol;
        expect(wrapper.vm.donationCurrency).to.be.equal(btcSymbol);

        wrapper.vm.selectedCurrency = tokSymbol;
        expect(wrapper.vm.donationCurrency).to.be.equal(tokSymbol);
    });

    it('should generate link to wallet for selected currency', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: true,
            },
        });

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.getDepositLink).to.be.equal('wallet');

        wrapper.vm.selectedCurrency = btcSymbol;
        expect(wrapper.vm.getDepositLink).to.be.equal('wallet');
    });

    it('should properly check if currency selected', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
            },
        });

        wrapper.vm.selectedCurrency = '';
        expect(wrapper.vm.isCurrencySelected).to.be.false;

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.isCurrencySelected).to.be.true;

        wrapper.vm.selectedCurrency = tokSymbol;
        expect(wrapper.vm.isCurrencySelected).to.be.false;

        wrapper.vm.selectedCurrency = btcSymbol;
        expect(wrapper.vm.isCurrencySelected).to.be.true;
    });

    it('should properly check insufficient funds', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
                market: {
                    base: {
                        subunit: 4,
                    },
                },
            },
        });

        wrapper.vm.balanceLoaded = false;
        expect(wrapper.vm.insufficientFunds).to.be.false;

        wrapper.vm.balanceLoaded = true;
        expect(wrapper.vm.insufficientFunds).to.be.true;

        wrapper.vm.balance = '0.5';
        expect(wrapper.vm.insufficientFunds).to.be.false;

        wrapper.vm.amountToDonate = '0.55';
        expect(wrapper.vm.insufficientFunds).to.be.true;
    });

    it('should properly check amount to donate', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
                donationParams: {
                    fee: 1,
                    minBtcAmount: 0.000001,
                    minWebAmount: 0.0001,
                },
            },
        });

        wrapper.vm.amountToDonate = '';
        expect(wrapper.vm.isAmountValid).to.be.false;

        wrapper.vm.amountToDonate = '0.0';
        expect(wrapper.vm.isAmountValid).to.be.false;

        wrapper.vm.amountToDonate = '0.001';
        expect(wrapper.vm.isAmountValid).to.be.true;

        wrapper.vm.amountToDonate = '0.0000';
        expect(wrapper.vm.isAmountValid).to.be.false;

        wrapper.vm.amountToDonate = '0.0001';
        expect(wrapper.vm.isAmountValid).to.be.true;

        wrapper.vm.selectedCurrency = btcSymbol;
        wrapper.vm.amountToDonate = 0.0000001;
        expect(wrapper.vm.isAmountValid).to.be.false;
        wrapper.vm.amountToDonate = 0.000001;
        expect(wrapper.vm.isAmountValid).to.be.true;

        wrapper.vm.selectedCurrency = webSymbol;
        wrapper.vm.amountToDonate = 0.00001;
        expect(wrapper.vm.isAmountValid).to.be.false;
        wrapper.vm.amountToDonate = 0.0001;
        expect(wrapper.vm.isAmountValid).to.be.true;
    });

    it('should properly disable button', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: false,
                market: {
                    base: {
                        subunit: 4,
                    },
                },
            },
        });

        expect(wrapper.vm.buttonDisabled).to.be.true;

        wrapper.vm.loggedIn = true;
        expect(wrapper.vm.buttonDisabled).to.be.true;

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.buttonDisabled).to.be.true;

        wrapper.vm.balanceLoaded = true;
        wrapper.vm.balance = '20';
        expect(wrapper.vm.buttonDisabled).to.be.true;

        wrapper.vm.amountToDonate = '5';
        expect(wrapper.vm.buttonDisabled).to.be.false;

        wrapper.vm.donationChecking = true;
        expect(wrapper.vm.buttonDisabled).to.be.true;

        wrapper.vm.donationChecking = false;
        wrapper.vm.donationInProgress = true;
        expect(wrapper.vm.buttonDisabled).to.be.true;
    });

    it('can select currency', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
            },
        });

        wrapper.vm.balanceLoaded = true;
        wrapper.vm.onSelect(webSymbol);
        expect(wrapper.vm.selectedCurrency).to.be.equal(webSymbol);
        expect(wrapper.vm.balanceLoaded).to.be.false;

        wrapper.vm.balanceLoaded = true;
        wrapper.vm.onSelect(btcSymbol);
        expect(wrapper.vm.selectedCurrency).to.be.equal(btcSymbol);
        expect(wrapper.vm.balanceLoaded).to.be.false;
    });

    it('can load login form', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: false,
            },
        });

        moxios.stubRequest('login', {
            status: 200,
            response: '<form></form>',
        });

        wrapper.vm.loadLoginForm();

        moxios.wait(() => {
            expect(wrapper.vm.loginFormLoaded).to.be.true;
            expect(wrapper.html()).to.contain('<form>');
            done();
        });
    });

    it('can load token balance', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(Donation, {
            localVue,
            propsData: {
                loggedIn: true,
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
                expect(wrapper.vm.balance).to.be.equal(111);
                expect(wrapper.vm.balanceLoaded).to.be.true;
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
                    minWebAmount: '0.0001',
                },
            },
        });

        moxios.stubRequest('check_donation', {
            status: 202,
            response: {
                amountToReceive: '2.5674',
            },
        });

        wrapper.vm.selectedCurrency = webSymbol;
        wrapper.vm.amountToDonate = 50;
        wrapper.vm.checkDonation();
        expect(wrapper.vm.donationChecking).to.be.true;

        moxios.wait(() => {
            expect(wrapper.vm.amountToReceive).to.be.equal('2.5674');
            expect(wrapper.vm.donationChecking).to.be.false;
            done();
        });
    });

    it('can make donation if logged in and currency selected and amount to donate/receive not null', (done) => {
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
            },
            data: {
                selectedCurrency: webSymbol,
                amountToDonate: 20,
                amountToReceive: 2,
                balanceLoaded: true,
                balance: 220,
            },
        });

        moxios.stubRequest('make_donation', {
            status: 202,
        });

        wrapper.vm.makeDonation();
        expect(wrapper.vm.donationInProgress).to.be.true;

        moxios.wait(() => {
            expect(wrapper.vm.amountToDonate).to.be.equal(0);
            expect(wrapper.vm.amountToReceive).to.be.equal(0);
            expect(wrapper.vm.donationInProgress).to.be.false;
            expect(wrapper.vm.balanceLoaded).to.be.false;
            done();
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
                    minWebAmount: '0.0001',
                },
            },
        });

        wrapper.vm.selectedCurrency = webSymbol;
        wrapper.vm.balance = 100;
        wrapper.vm.amountToDonate = 20;
        wrapper.vm.all();

        expect(wrapper.vm.amountToDonate).to.be.equal('100');

        // Insufficient funds
        wrapper.vm.balance = 100;
        wrapper.vm.amountToDonate = 120;
        wrapper.vm.all();

        expect(wrapper.vm.amountToDonate).to.be.equal('100');
    });

    it('should reset amount to donate and amount to receive on calling resetAmount()', () => {
        const wrapper = shallowMount(Donation, {
            propsData: {
                loggedIn: true,
            },
        });

        wrapper.vm.amountToDonate = 50;
        wrapper.vm.amountToReceive = 7;
        wrapper.vm.resetAmount();

        expect(wrapper.vm.amountToDonate).to.be.equal(0);
        expect(wrapper.vm.amountToReceive).to.be.equal(0);
    });
});
