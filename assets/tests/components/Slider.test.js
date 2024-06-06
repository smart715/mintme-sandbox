import {shallowMount, createLocalVue} from '@vue/test-utils';
import Slider from '../../js/components/UI/Slider.vue';

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
        value: 22,
        maxValue: 100,
        intervalProp: 1,
        disabled: false,
        tooltipFormatter: '%',
        tabindex: '1',
        precision: null,
        ...props,
    };
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockSlider(props = {}) {
    return shallowMount(Slider, {
        localVue: localVue,
        propsData: createSharedTestProps(props),
    });
}

describe('Slider', () => {
    it('Verify that "localValue" returns the correct value', () => {
        const wrapper = mockSlider();

        expect(wrapper.vm.localValue).toBe('22');
    });

    it('Verify that "marks" returns the correct value', async () => {
        const wrapper = mockSlider();

        expect(wrapper.vm.marks).toEqual([0, 25, 50, 75, 100]);

        await wrapper.setProps({maxValue: 200});

        expect(wrapper.vm.marks).toEqual([0, 50, 100, 150, 200]);
    });

    it('Verify that "interval" returns the correct value', async () => {
        const wrapper = mockSlider();

        expect(wrapper.vm.interval).toBe(1);

        await wrapper.setProps({
            intervalProp: 2,
        });

        expect(wrapper.vm.interval).toBe(2);
    });


    it('Verify that "percentAmountTotal" returns the correct value', async () => {
        const wrapper = mockSlider();

        expect(wrapper.vm.percentAmountTotal).toBe('22%');

        await wrapper.setProps({value: '4.5'});

        expect(wrapper.vm.percentAmountTotal).toBe('5%');
    });
});
