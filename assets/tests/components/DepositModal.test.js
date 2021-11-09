import {shallowMount, createLocalVue} from '@vue/test-utils';
import DepositModal from '../../js/components/modal/DepositModal';
import Modal from '../../js/components/modal/Modal';

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

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

describe('DepositModal', () => {
    it('should be visible when visible props is true', () => {
        const wrapper = shallowMount(DepositModal, {
            localVue: mockVue(),
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
        expect(wrapper.vm.visible).toBe(true);
    });

    it('should provide closing on ESC and closing on backdrop click when noClose props is false', () => {
        const wrapper = shallowMount(DepositModal, {
            localVue: mockVue(),
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
        expect(wrapper.vm.noClose).toBe(false);
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = shallowMount(DepositModal, {
            localVue: mockVue(),
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
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('emit "success" when clicking on button "OK"', () => {
        const wrapper = shallowMount(DepositModal, {
            localVue: mockVue(),
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
        expect(wrapper.emitted('success').length).toBe(1);
    });

    it('emit "success" when the function onSuccess() is called', () => {
        const wrapper = shallowMount(DepositModal, {
            localVue: mockVue(),
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
        expect(wrapper.emitted('success').length).toBe(1);
    });

    it('should be equal "WEB" when isToken props is true', () => {
        const wrapper = shallowMount(DepositModal, {
            localVue: mockVue(),
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
        expect(wrapper.vm.feeCurrency).toBe('WEB');
    });

    it('should be equal "webTest" when isToken props is false', () => {
        const wrapper = shallowMount(DepositModal, {
            localVue: mockVue(),
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
        expect(wrapper.vm.feeCurrency).toBe('webTest');
    });

    it('should be contain "addressTest" in the address field', () => {
        const wrapper = shallowMount(DepositModal, {
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            stubs: {
                Modal: Modal,
            },
            localVue: mockVue(),
            children: [Modal],
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

        expect(wrapper.html().includes('addressTest')).toBe(true);
    });

    it('should be contain "MintMe Coin Test" in the description field', () => {
        const wrapper = shallowMount(DepositModal, {
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            stubs: {
                Modal: Modal,
            },
            localVue: mockVue(),
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

        expect(wrapper.html().includes('MintMe Coin Test')).toBe(true);
    });

    it('should be contain "mintimeTest" in the min field', () => {
        const wrapper = shallowMount(DepositModal, {
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            stubs: {
                Modal: Modal,
            },
            localVue: mockVue(),
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

        expect(wrapper.html().includes('mintimeTest')).toBe(true);
    });

    it('should be contain "mintimeTest" in the fee field', () => {
        const wrapper = shallowMount(DepositModal, {
            stubs: {
                Modal: Modal,
            },
            filters: {
                rebranding: (val) => rebrandingTest(val),
            },
            localVue: mockVue(),
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

        expect(wrapper.html().includes('mintimeTest')).toBe(true);
    });
});
