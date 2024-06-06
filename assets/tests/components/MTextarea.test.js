import {shallowMount, createLocalVue} from '@vue/test-utils';
import MTextarea from '../../js/components/UI/Textarea.vue';

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
function mockMTextarea(props = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(MTextarea, {
        localVue: localVue,
        propsData: {
            maxLength: null,
            inputId: null,
            rows: 5,
            textareaTabIndex: '',
            ...props,
        },
    });

    return wrapper;
}

describe('MTextarea', () => {
    it('Verify that "onChange" works correctly', () => {
        const wrapper = mockMTextarea();

        wrapper.vm.onChange();

        expect(wrapper.emitted().change).toBeTruthy();
        expect(wrapper.emitted().change[0]).toEqual([wrapper.vm.value]);
    });

    it('Verify that "onInput" works correctly', () => {
        const wrapper = mockMTextarea();

        wrapper.vm.onInput();

        expect(wrapper.emitted().input).toBeTruthy();
        expect(wrapper.emitted().input[0]).toEqual([wrapper.vm.value]);
    });

    it('Verify that the spinner is shown and hidden', async () => {
        const wrapper = mockMTextarea();

        expect(wrapper.findComponent('.spinner-border').exists()).toBe(false);

        await wrapper.setProps({loading: true});
        expect(wrapper.findComponent('.spinner-border').exists()).toBe(true);
    });
});
