import {shallowMount, createLocalVue} from '@vue/test-utils';
import '../__mocks__/ResizeObserver';
import PriceConverterInput from '../../js/components/PriceConverterInput';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} data
 * @return {Wrapper<Vue>}
 */
function mockPriceConverterInput(props = {}, data = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(PriceConverterInput, {
        localVue: localVue,
        propsData: {
            value: '10',
            disabled: true,
            tabindex: '',
            inputId: '',
            from: '',
            to: '',
            symbol: '',
            subunit: 4,
            convert: null,
            showConverter: true,
            inputClass: {},
            ...props,
        },
        data() {
            return {
                resizeObserver: null,
                ...data,
            };
        },
    });

    return wrapper;
}


describe('PriceConverterInput', () => {
    it('Verify that `input` event is emitted correctly', () => {
        const wrapper = mockPriceConverterInput();

        wrapper.vm.onInput();

        expect(wrapper.emitted().input).toBeTruthy();
        expect(wrapper.emitted().input[0]).toEqual([wrapper.vm.value]);
    });

    it('Verify that "updateInputWidth" works correctly', () => {
        const wrapper = mockPriceConverterInput();
        const input = wrapper.findComponent({ref: 'input'}).element;

        wrapper.vm.updateInputWidth.bind(input);

        expect(wrapper.vm.inputWidth).toBe(100);
    });

    it('Verify that "amountToConvert" return the correct value', async () => {
        const wrapper = mockPriceConverterInput();

        await wrapper.setProps({convert: 100});
        expect(wrapper.vm.amountToConvert).toBe(100);

        await wrapper.setProps({convert: null});
        expect(wrapper.vm.amountToConvert).toBe(wrapper.vm.value);
    });

    it('Verify that "overflow" return the correct value', async () => {
        const wrapper = mockPriceConverterInput();

        await wrapper.setProps({symbol: '$'});
        await wrapper.setData({
            newValue: wrapper.vm.value,
            inputWidth: 10,
        });

        expect(wrapper.vm.overflow).toBe(true);

        await wrapper.setData({
            inputWidth: 50,
        });

        expect(wrapper.vm.overflow).toBe(false);
    });
});
