import Vue from 'vue';
import {shallowMount, mount} from '@vue/test-utils';
import OrderModal from '../../js/components/modal/OrderModal';
Vue.use({
    install(Vue, options) {
        Vue.prototype.$t = (val) => val;
    },
});

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
            mocks: {$t: (val) => val},
        });
        expect(wrapper.vm.visible).toBe(true);
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = shallowMount(OrderModal, {
            propsData: {
                type: true,
                title: '',
                visible: true,
            },
        });
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
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
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('should be contain "order-created" in img field when type props is true', () => {
        const wrapper = shallowMount(OrderModal, {
            propsData: {
                type: true,
                title: '',
                visible: true,
            },
        });
        expect(wrapper.find('img').attributes('src')).toEqual(
            expect.stringContaining('order-created')
        );
    });

    it('should be contain "order-failed" in img field when type props is false', () => {
        const wrapper = shallowMount(OrderModal, {
            propsData: {
                type: false,
                title: '',
                visible: true,
            },
        });
        expect(wrapper.find('img').attributes('src')).toEqual(
            expect.stringContaining('order-failed')
        );
    });

    it('should be contain "mintimeTest" in the title field', () => {
        const wrapper = mount(OrderModal, {
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
            stubs: {
                Modal: {template: '<div><slot name="body"></slot></div>'},
            },
        });

        expect(wrapper.html()).toEqual(
            expect.stringContaining('mintimeTest')
        );
    });
});
