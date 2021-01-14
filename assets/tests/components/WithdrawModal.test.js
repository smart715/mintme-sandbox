import Vuelidate from 'vuelidate';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import WithdrawModal from '../../js/components/modal/WithdrawModal';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuelidate);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
};

let rebrandingTest = (val) => {
    if (!val) {
        return val;
    }

    const brandDict = [
        {regexp: /(webTest)/g, replacer: 'mintimeTest'},
    ];
    brandDict.forEach((item) => {
        if (typeof val !== 'string') {
            return;
        }
        val = val.replace(item.regexp, item.replacer);
    });

    return val;
};

let propsForTestCorrectlyRenders = {
    visible: true,
    currency: 'Token',
    isToken: true,
    fee: '0',
    baseFee: '0',
    baseSymbol: 'WEB',
    withdrawUrl: 'withdraw_url',
    maxAmount: '0',
    availableBase: '0',
    subunit: 0,
    twofa: '0',
    noClose: false,
};

describe('WithdrawModal', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    const addressOk = '0xfB6916095ca1df60bB79Ce92cE3Ea74c37c5d359';
    const addressNotOk = '00fB6916095ca1df60bB79Ce92cE3Ea74c37c5d359';
    const amountOk = 100;
    const subunitOk = 0;
    const maxAmountOk = '1000';
    const code = '123456';
    it('should be visible when visible props is true', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        expect(wrapper.vm.visible).toBe(true);
    });

    it('should provide closing on ESC and closing on backdrop click when noClose props is false', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        expect(wrapper.vm.noClose).toBe(false);
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('emit "cancel" and "close" when clicking on button "Cancel"', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.find('button.btn-cancel.pl-3.c-pointer').trigger('click');
        expect(wrapper.emitted('cancel').length).toBe(1);
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('should be equal "0.001" when subunit props is equal 0.001', () => {
        propsForTestCorrectlyRenders.subunit = 3;
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        propsForTestCorrectlyRenders.subunit = 0;
        expect(wrapper.vm.minAmount).toBe('0.001');
    });

    it('should be contain "123456789" in the "Withdrawal fee" field  when isToken props is true', () => {
        propsForTestCorrectlyRenders.webFee = '123456789';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
            stubs: {
                Modal: {template: '<div><slot name="body"></slot></div>'},
            },
        });
        propsForTestCorrectlyRenders.webFee = '0';
        expect(wrapper.html().includes('123456789')).toBe(true);
    });

    it('should be contain "987654321" in the "Withdrawal fee" field  when isToken props is false', () => {
        propsForTestCorrectlyRenders.isToken = false;
        propsForTestCorrectlyRenders.fee = '987654321';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
            stubs: {
                Modal: {template: '<div><slot name="body"></slot></div>'},
            },
        });
        propsForTestCorrectlyRenders.isToken = true;
        propsForTestCorrectlyRenders.fee = '0';
        expect(wrapper.html().includes('987654321')).toBe(true);
    });

    it('should be equal "WEB" when isToken props is true', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        expect(wrapper.vm.feeCurrency).toBe('Token');
    });

    it('should be equal "webTest" when isToken props is false', () => {
        propsForTestCorrectlyRenders.isToken = false;
        propsForTestCorrectlyRenders.currency = 'webTest';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        propsForTestCorrectlyRenders.isToken = true;
        propsForTestCorrectlyRenders.currency = '';
        expect(wrapper.vm.feeCurrency).toBe('webTest');
    });

    it('should be contain "mintimeTest" in the form', () => {
        propsForTestCorrectlyRenders.isToken = false;
        propsForTestCorrectlyRenders.currency = 'webTest';
        const wrapper = shallowMount(WithdrawModal, {
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
            stubs: {
                Modal: {template: '<div><slot name="body"></slot></div>'},
            },
        });
        propsForTestCorrectlyRenders.isToken = true;
        propsForTestCorrectlyRenders.currency = '';
        expect(wrapper.html().includes('mintimeTest')).toBe(true);
    });

    it('should be equal "15912.12" in the "Total to be withdrawn" field', () => {
        propsForTestCorrectlyRenders.subunit = 2;
        propsForTestCorrectlyRenders.fee = '3567';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.amount = 12345.1234;
        propsForTestCorrectlyRenders.subunit = 0;
        propsForTestCorrectlyRenders.fee = '0';
        expect(wrapper.vm.fullAmount).toBe('15912.12');
    });

    it('should be equal "48023" in the "Total to be withdrawn" field', () => {
        propsForTestCorrectlyRenders.fee = '35678';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.amount = 12345;
        propsForTestCorrectlyRenders.fee = '0';
        expect(wrapper.vm.fullAmount).toBe('48023');
    });

    it('should\'t be equal "12345f" in the "Total to be withdrawn" field', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.amount = '12345f';
        expect(wrapper.vm.fullAmount).toBe('0');
    });

    it('should be false when address data is incorrect', () => {
        propsForTestCorrectlyRenders.currency = 'BTC';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.address = '';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.address.$error).toBe(false);

        wrapper.vm.address = 'ab-cd';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.address.$error).toBe(false);

        wrapper.vm.address = 'abcd';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.address.$error).toBe(false);

        wrapper.vm.address = 'abcd1234567890123456789012345678901234567890';
        wrapper.vm.$v.$touch();
        propsForTestCorrectlyRenders.currency = '';
        expect(!wrapper.vm.$v.address.$error).toBe(false);
    });

    it('should be true when address data is correct', () => {
        propsForTestCorrectlyRenders.currency = 'BTC';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.address = addressOk;
        wrapper.vm.$v.$touch();
        propsForTestCorrectlyRenders.currency = '';
        expect(!wrapper.vm.$v.address.$error).toBe(true);
    });

    it('should be false when amount data is incorrect', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.amount = '';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).toBe(false);

        wrapper.vm.amount = 'abcd';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).toBe(false);

        wrapper.vm.amount = 0.1;
        wrapper.setProps({subunit: 0});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).toBe(false);

        wrapper.vm.amount = 1000;
        wrapper.setProps({maxAmount: '100'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).toBe(false);
    });

    it('should be true when amount data is correct', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.amount = amountOk;
        wrapper.setProps({subunit: subunitOk});
        wrapper.setProps({maxAmount: maxAmountOk});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).toBe(true);
    });

    it('calculate the amount correctly when the function setMaxAmount() is called', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.setProps({maxAmount: '1000'});
        wrapper.setProps({subunit: 2});
        wrapper.setProps({fee: '123.1234'});
        wrapper.vm.setMaxAmount();
        expect(wrapper.vm.amount).toBe('876.87');

        wrapper.setProps({maxAmount: '100'});
        wrapper.setProps({subunit: 2});
        wrapper.setProps({fee: '123.1234'});
        wrapper.vm.setMaxAmount();
        expect(wrapper.vm.amount).toBe('0');
    });

    it('do $axios request and emit "withdraw" when the function onWithdraw() is called and when data is correct', (done) => {
        propsForTestCorrectlyRenders.currency = 'BTC';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.address = addressOk;
        wrapper.vm.amount = amountOk;
        wrapper.vm.code = code;
        wrapper.setProps({subunit: subunitOk});
        wrapper.setProps({maxAmount: maxAmountOk});
        wrapper.vm.$v.$touch();
        wrapper.vm.onWithdraw();
        expect(wrapper.emitted('withdraw').length).toBe(1);

        moxios.stubRequest('withdraw_url', {
            status: 200,
        });

        moxios.wait(() => {
            expect(wrapper.vm.withdrawing).toBe(false);
            done();
        });
    });

    it('provide address without "0x" for web currencies', () => {
        propsForTestCorrectlyRenders.currency = 'WEB';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });
        wrapper.vm.address = addressNotOk;
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.address.$error).toBe(true);
    });
});
