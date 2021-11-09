import {shallowMount, createLocalVue} from '@vue/test-utils';
import FaqItem from '../../js/components/FaqItem';

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

describe('FaqItem', () => {
    it('show "chevron-down" icon if the component variable contains this value', () => {
        const wrapper = shallowMount(FaqItem, {
            propsData: {
                icon: 'chevron-down',
            },
            localVue: mockVue(),
        });
        expect(wrapper.attributes('icon')).toBe('chevron-down');
    });

    it('show "chevron-up" icon if the component variable contains this value', () => {
        const wrapper = shallowMount(FaqItem, {
            propsData: {
                icon: 'chevron-up',
            },
            localVue: mockVue(),
        });
        expect(wrapper.attributes('icon')).toBe('chevron-up');
    });

    it('switch the chevron-down icon to the chevron-up icon', () => {
        const wrapper = shallowMount(FaqItem, {
            localVue: mockVue(),
        });
        wrapper.vm.icon = 'chevron-down';
        wrapper.vm.switchIcon();
        expect(wrapper.vm.icon).toBe('chevron-up');
    });

    it('switch the chevron-up icon to the chevron-down icon', () => {
        const wrapper = shallowMount(FaqItem, {
            localVue: mockVue(),
        });
        wrapper.vm.icon = 'chevron-up';
        wrapper.vm.switchIcon();
        expect(wrapper.vm.icon).toBe('chevron-down');
    });

    it('emit "switch"', () => {
        const wrapper = shallowMount(FaqItem, {
            localVue: mockVue(),
        });
        wrapper.vm.switchIcon();
        expect(wrapper.emitted('switch').length).toBe(1);
    });
});
