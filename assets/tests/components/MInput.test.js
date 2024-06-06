import {shallowMount, createLocalVue} from '@vue/test-utils';
import MInput from '../../js/components/UI/Input.vue';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockMInput(props = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(MInput, {
        localVue: localVue,
        propsData: {
            value: 'TextValue',
            maxLength: null,
            autocomplete: '',
            inputTabIndex: '',
            ...props,
        },
    });

    return wrapper;
}

describe('MInput', () => {
    it('Verify that "onChange" works correctly', () => {
        const wrapper = mockMInput();

        wrapper.vm.onChange();

        expect(wrapper.emitted().change).toBeTruthy();
        expect(wrapper.emitted().change[0]).toEqual([wrapper.vm.value]);
    });

    it('Verify that "onInput" works correctly', () => {
        const wrapper = mockMInput();

        wrapper.vm.onInput();

        expect(wrapper.emitted().input).toBeTruthy();
        expect(wrapper.emitted().input[0]).toEqual([wrapper.vm.value]);
    });

    it('Verify that the spinner is shown and hidden', async () => {
        const wrapper = mockMInput();

        expect(wrapper.findComponent('.spinner-border').exists()).toBe(false);

        await wrapper.setProps({loading: true});
        expect(wrapper.findComponent('.spinner-border').exists()).toBe(true);
    });

    it('Verify that the value in `localValue` is set correctly', async () => {
        const wrapper = mockMInput();

        expect(wrapper.vm.localValue).toBe(wrapper.vm.value);

        await wrapper.setProps({value: 'jasm'});

        expect(wrapper.vm.localValue).toBe('jasm');
    });
});
