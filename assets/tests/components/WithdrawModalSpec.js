import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import WithdrawModal from '../../js/components/modal/WithdrawModal';
import moxios from 'moxios';
import axios from 'axios';
Vue.use(Vuelidate);
Vue.use(Toasted);

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {single: axios};
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
    currency: '',
    isToken: true,
    fee: '0',
    webFee: '0',
    withdrawUrl: 'withdraw_url',
    maxAmount: '0',
    availableWeb: '0',
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

    it('should be visible when visible props is true', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.visible).to.be.true;
    });

    it('should provide closing on ESC and closing on backdrop click when noClose props is false', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.noClose).to.be.false;
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).to.be.equal(1);
    });

    it('emit "cancel" and "close" when clicking on span "Cancel"', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.find('span.btn-cancel.pl-3.c-pointer').trigger('click');
        expect(wrapper.emitted('cancel').length).to.be.equal(1);
        expect(wrapper.emitted('close').length).to.be.equal(1);
    });

    it('should be equal "0.001" when subunit props is equal 0.001', () => {
        propsForTestCorrectlyRenders.subunit = 3;
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        propsForTestCorrectlyRenders.subunit = 0;
        expect(wrapper.vm.minAmount).to.be.deep.equal('0.001');
    });

    it('should be contain "123456789" in the "Withdrawal fee" field  when isToken props is true', () => {
        propsForTestCorrectlyRenders.webFee = '123456789';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        propsForTestCorrectlyRenders.webFee = '0';
        expect(wrapper.html()).to.contain('123456789');
    });

    it('should be contain "987654321" in the "Withdrawal fee" field  when isToken props is false', () => {
        propsForTestCorrectlyRenders.isToken = false;
        propsForTestCorrectlyRenders.fee = '987654321';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        propsForTestCorrectlyRenders.isToken = true;
        propsForTestCorrectlyRenders.fee = '0';
        expect(wrapper.html()).to.contain('987654321');
    });

    it('should be equal "WEB" when isToken props is true', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.feeCurrency).to.be.equal('WEB');
    });

    it('should be equal "webTest" when isToken props is false', () => {
        propsForTestCorrectlyRenders.isToken = false;
        propsForTestCorrectlyRenders.currency = 'webTest';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        propsForTestCorrectlyRenders.isToken = true;
        propsForTestCorrectlyRenders.currency = '';
        expect(wrapper.vm.feeCurrency).to.be.equal('webTest');
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
        });
        propsForTestCorrectlyRenders.isToken = true;
        propsForTestCorrectlyRenders.currency = '';
        expect(wrapper.html()).to.contain('mintimeTest');
    });

    it('should be equal "15912.12" in the "Total to be withdrawn" field', () => {
        propsForTestCorrectlyRenders.subunit = 2;
        propsForTestCorrectlyRenders.fee = '3567';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.amount = 12345.1234;
        propsForTestCorrectlyRenders.subunit = 0;
        propsForTestCorrectlyRenders.fee = '0';
        expect(wrapper.vm.fullAmount).to.be.equal('15912.12');
    });

    it('should be equal "12345" in the "Total to be withdrawn" field when fee props is greater than amount data', () => {
        propsForTestCorrectlyRenders.fee = '35678';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.amount = 12345;
        propsForTestCorrectlyRenders.fee = '0';
        expect(wrapper.vm.fullAmount).to.be.equal('12345');
    });

    it('should\'t be equal "12345f" in the "Total to be withdrawn" field', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.amount = '12345f';
        expect(wrapper.vm.fullAmount).to.be.equal('0');
    });

    it('should be false when address data is incorrect', () => {
        propsForTestCorrectlyRenders.currency = 'BTC';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.address = '';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.address.$error).to.be.false;

        wrapper.vm.address = 'ab-cd';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.address.$error).to.be.false;

        wrapper.vm.address = 'abcd';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.address.$error).to.be.false;

        wrapper.vm.address = 'abcd1234567890123456789012345678901234567890';
        wrapper.vm.$v.$touch();
        propsForTestCorrectlyRenders.currency = '';
        expect(!wrapper.vm.$v.address.$error).to.be.false;
    });

    it('should be true when address data is correct', () => {
        propsForTestCorrectlyRenders.currency = 'BTC';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.address = addressOk;
        wrapper.vm.$v.$touch();
        propsForTestCorrectlyRenders.currency = '';
        expect(!wrapper.vm.$v.address.$error).to.be.true;
    });

    it('should be false when amount data is incorrect', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.amount = '';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).to.be.false;

        wrapper.vm.amount = 'abcd';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).to.be.false;

        wrapper.vm.amount = 0.1;
        wrapper.vm.subunit = 0;
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).to.be.false;

        wrapper.vm.amount = 1000;
        wrapper.vm.maxAmount = '100';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).to.be.false;
    });

    it('should be true when amount data is correct', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.amount = amountOk;
        wrapper.vm.subunit = subunitOk;
        wrapper.vm.maxAmount = maxAmountOk;
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.$error).to.be.true;
    });

    it('calculate the amount correctly when the function setMaxAmount() is called', () => {
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.maxAmount = '1000';
        wrapper.vm.subunit = 2;
        wrapper.vm.fee = '123.1234';
        wrapper.vm.setMaxAmount();
        expect(wrapper.vm.amount).to.be.equal('876.87');

        wrapper.vm.maxAmount = '100';
        wrapper.vm.subunit = 2;
        wrapper.vm.fee = '123.1234';
        wrapper.vm.setMaxAmount();
        expect(wrapper.vm.amount).to.be.equal('0');
    });

    it('do $axios request and emit "withdraw" when the function onWithdraw() is called and when data is correct', (done) => {
        const localVue = mockVue();
        propsForTestCorrectlyRenders.currency = 'BTC';
        const wrapper = shallowMount(WithdrawModal, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.address = addressOk;
        wrapper.vm.amount = amountOk;
        wrapper.vm.subunit = subunitOk;
        wrapper.vm.maxAmount = maxAmountOk;
        wrapper.vm.$v.$touch();
        wrapper.vm.onWithdraw();
        expect(wrapper.emitted('withdraw').length).to.be.equal(1);

        moxios.stubRequest('withdraw_url', {
            status: 202,
        });

        moxios.wait(() => {
            expect(wrapper.vm.withdrawing).to.be.false;
            done();
        });
    });

    it('provide address without "0x" for web currencies', () => {
        propsForTestCorrectlyRenders.currency = 'WEB';
        const wrapper = shallowMount(WithdrawModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.address = addressNotOk;
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.address.$error).to.be.true;
    });
});
