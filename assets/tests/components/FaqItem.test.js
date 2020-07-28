import {mount} from '@vue/test-utils';
import FaqItem from '../../js/components/FaqItem';

describe('FaqItem', () => {
    it('show "chevron-down" icon if the component variable contains this value', () => {
        const wrapper = mount(FaqItem, {
            propsData: {
                icon: 'chevron-down',
            },
        });
        expect(wrapper.attributes('icon')).toBe('chevron-down');
    });

    it('show "chevron-up" icon if the component variable contains this value', () => {
        const wrapper = mount(FaqItem, {
            propsData: {
                icon: 'chevron-up',
            },
        });
        expect(wrapper.attributes('icon')).toBe('chevron-up');
    });

    it('switch the chevron-down icon to the chevron-up icon', () => {
        const wrapper = mount(FaqItem, {});
        wrapper.vm.icon = 'chevron-down';
        wrapper.vm.switchIcon();
        expect(wrapper.vm.icon).toBe('chevron-up');
    });

    it('switch the chevron-up icon to the chevron-down icon', () => {
        const wrapper = mount(FaqItem, {});
        wrapper.vm.icon = 'chevron-up';
        wrapper.vm.switchIcon();
        expect(wrapper.vm.icon).toBe('chevron-down');
    });

    it('emit "switch"', () => {
        const wrapper = mount(FaqItem, {});
        wrapper.vm.switchIcon();
        expect(wrapper.emitted('switch').length).toBe(1);
    });
});
