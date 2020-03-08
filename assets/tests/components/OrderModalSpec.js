import {shallowMount} from '@vue/test-utils';
import OrderModal from '../../js/components/modal/OrderModal';

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

describe('OrderModal', () => {
    it('should be visible when visible props is true', () => {
        const wrapper = shallowMount(OrderModal, {
            propsData: {
                type: true,
                title: '',
                visible: true,
            },
        });
        expect(wrapper.vm.visible).to.be.true;
    });

    it('emit "close" when the function closeModal() is running', () => {
        const wrapper = shallowMount(OrderModal, {
            propsData: {
                type: true,
                title: '',
                visible: true,
            },
        });
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).to.be.equal(1);
    });

    it('emit "close" when clicking on button "OK"', () => {
        const wrapper = shallowMount(OrderModal, {
            propsData: {
                type: true,
                title: '',
                visible: true,
            },
        });
        wrapper.find('button.btn.btn-primary').trigger('click');
        expect(wrapper.emitted('close').length).to.be.equal(1);
    });

    it('should be contain "order-created.png" in img field when type props is true', () => {
        const wrapper = shallowMount(OrderModal, {
            propsData: {
                type: true,
                title: '',
                visible: true,
            },
        });
        expect(wrapper.find('img').attributes('src')).to.contain('order-created');
    });

    it('should be contain "order-failed.png" in img field when type props is false', () => {
        const wrapper = shallowMount(OrderModal, {
            propsData: {
                type: false,
                title: '',
                visible: true,
            },
        });
        expect(wrapper.find('img').attributes('src')).to.contain('order-failed');
    });

    it('should be contain "addressTest" in the address field', () => {
        const wrapper = shallowMount(OrderModal, {
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            propsData: {
                type: true,
                title: 'webTest',
                visible: true,
            },
        });
        expect(wrapper.html()).to.contain('mintimeTest');
    });
});
