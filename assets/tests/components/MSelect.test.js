import {shallowMount, createLocalVue} from '@vue/test-utils';
import MSelect from '../../js/components/UI/Select.vue';

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
function mockMSelect(props = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(MSelect, {
        localVue: localVue,
        propsData: {
            type: '',
            loading: false,
            selectTabIndex: '',
            ...props,
        },
    });

    return wrapper;
}

describe('MSelect', () => {
    it('Verify that "onChange" works correctly', async () => {
        const wrapper = mockMSelect();

        await wrapper.findComponent('select').trigger('change');

        expect(wrapper.emitted().change).toBeTruthy();
    });

    it('Verify that the spinner is shown and hidden', async () => {
        const wrapper = mockMSelect();

        expect(wrapper.findComponent('.spinner-border').exists()).toBe(false);

        await wrapper.setProps({loading: true});
        expect(wrapper.findComponent('.spinner-border').exists()).toBe(true);
    });
});
