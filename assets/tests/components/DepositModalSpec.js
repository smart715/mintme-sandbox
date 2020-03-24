import {shallowMount} from '@vue/test-utils';
import DepositModal from '../../js/components/modal/DepositModal';

let rebrandingTest = (val) => {
    if (!val) {
        return val;
    }

    const brandDict = [
        {regexp: /(WebchainTest)/g, replacer: 'MintMe Coin Test'},
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

describe('DepositModal', () => {
    it('should be visible when visible props is true', () => {
        const wrapper = shallowMount(DepositModal, {
            propsData: {
                visible: true,
                address: '',
                description: '',
                currency: '',
                isToken: true,
                min: '',
                fee: '',
                noClose: false,
            },
        });
        expect(wrapper.vm.visible).to.be.true;
    });

    it('should provide closing on ESC and closing on backdrop click when noClose props is false', () => {
        const wrapper = shallowMount(DepositModal, {
            propsData: {
                visible: true,
                address: '',
                description: '',
                currency: '',
                isToken: true,
                min: '',
                fee: '',
                noClose: false,
            },
        });
        expect(wrapper.vm.noClose).to.be.false;
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = shallowMount(DepositModal, {
            propsData: {
                visible: true,
                address: '',
                description: '',
                currency: '',
                isToken: true,
                min: '',
                fee: '',
                noClose: false,
            },
        });
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).to.be.equal(1);
    });

    it('emit "success" when clicking on button "OK"', () => {
        const wrapper = shallowMount(DepositModal, {
            propsData: {
                visible: true,
                address: '',
                description: '',
                currency: '',
                isToken: true,
                min: '',
                fee: '',
                noClose: false,
            },
        });
        wrapper.find('button.btn.btn-primary').trigger('click');
        expect(wrapper.emitted('success').length).to.be.equal(1);
    });

    it('emit "success" when the function onSuccess() is called', () => {
        const wrapper = shallowMount(DepositModal, {
            propsData: {
                visible: true,
                address: '',
                description: '',
                currency: '',
                isToken: true,
                min: '',
                fee: '',
                noClose: false,
            },
        });
        wrapper.vm.onSuccess();
        expect(wrapper.emitted('success').length).to.be.equal(1);
    });

    it('should be equal "WEB" when isToken props is true', () => {
        const wrapper = shallowMount(DepositModal, {
            propsData: {
                visible: true,
                address: '',
                description: '',
                currency: '',
                isToken: true,
                min: '',
                fee: '',
                noClose: false,
            },
        });
        expect(wrapper.vm.feeCurrency).to.be.equal('WEB');
    });

    it('should be equal "webTest" when isToken props is false', () => {
        const wrapper = shallowMount(DepositModal, {
            propsData: {
                visible: true,
                address: '',
                description: '',
                currency: 'webTest',
                isToken: false,
                min: '',
                fee: '',
                noClose: false,
            },
        });
        expect(wrapper.vm.feeCurrency).to.be.equal('webTest');
    });

    it('should be contain "addressTest" in the address field', () => {
        const wrapper = shallowMount(DepositModal, {
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            propsData: {
                visible: true,
                address: 'addressTest',
                description: '',
                currency: '',
                isToken: false,
                min: '',
                fee: '',
                noClose: true,
            },
        });
        expect(wrapper.html()).to.contain('addressTest');
    });

    it('should be contain "MintMe Coin Test" in the description field', () => {
        const wrapper = shallowMount(DepositModal, {
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            propsData: {
                visible: true,
                address: '',
                description: 'WebchainTest',
                currency: '',
                isToken: false,
                min: '',
                fee: '',
                noClose: true,
            },
        });
        expect(wrapper.html()).to.contain('MintMe Coin Test');
    });

    it('should be contain "mintimeTest" in the min field', () => {
        const wrapper = shallowMount(DepositModal, {
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            propsData: {
                visible: true,
                address: '',
                description: '',
                currency: 'webTest',
                isToken: false,
                min: 'minTest',
                fee: '',
                noClose: true,
            },
        });
        expect(wrapper.html()).to.contain('mintimeTest');
    });

    it('should be contain "mintimeTest" in the fee field', () => {
        const wrapper = shallowMount(DepositModal, {
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            propsData: {
                visible: true,
                address: '',
                description: '',
                currency: 'webTest',
                isToken: false,
                min: '',
                fee: 'feeTest',
                noClose: true,
            },
        });
        expect(wrapper.html()).to.contain('mintimeTest');
    });
});
