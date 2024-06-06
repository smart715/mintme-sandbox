import {shallowMount, createLocalVue} from '@vue/test-utils';
import CounterWrapper from '../../js/components/CounterWrapper.vue';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    return createLocalVue();
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        count: 0,
        block: false,
        icon: true,
        ...props,
    };
}

describe('CounterWrapper', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(CounterWrapper, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    it('Verify that the counter is shown and hidden', async () => {
        expect(wrapper.findComponent('.counter-icon').exists()).toBe(false);

        await wrapper.setProps({count: 5});
        expect(wrapper.findComponent('.counter-icon').exists()).toBe(true);
    });

    it('Should have .block class in case of block = true', async () => {
        await wrapper.setProps({count: 5, block: false});

        expect(wrapper.findComponent('.block').exists()).toBe(false);

        await wrapper.setProps({block: true});
        expect(wrapper.findComponent('.block').exists()).toBe(true);
    });

    it('Should have .custom-icon and .position-absolute classes in case of icon = true', async () => {
        await wrapper.setProps({count: 5, icon: false});

        expect(wrapper.findComponent('.custom-icon').exists()).toBe(false);
        expect(wrapper.findComponent('.position-absolute').exists()).toBe(false);

        await wrapper.setProps({icon: true});
        expect(wrapper.findComponent('.custom-icon').exists()).toBe(true);
        expect(wrapper.findComponent('.position-absolute').exists()).toBe(true);
    });

    it('showCounter should work properly with different values', async () => {
        expect(wrapper.vm.showCounter).toBe(false);

        await wrapper.setProps({count: 5});
        expect(wrapper.vm.showCounter).toBe(true);
    });

    it('countHumanized should work properly', async () => {
        await wrapper.setProps({count: 5});
        expect(wrapper.vm.countHumanized).toBe(5);

        await wrapper.setProps({count: 100});
        expect(wrapper.vm.countHumanized).toBe('99+');
    });
});
