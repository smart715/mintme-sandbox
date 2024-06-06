import {shallowMount, createLocalVue} from '@vue/test-utils';
import FormControlWrapper from '../../js/components/UI/FormControlWrapper.vue';

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
function mockFormControlWrapper(props = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(FormControlWrapper, {
        localVue: localVue,
        propsData: {
            type: '',
            loading: false,
            labelPointerEvents: false,
            ...props,
        },
    });

    return wrapper;
}

describe('FormControlWrapper', () => {
    it('Verify that the spinner is shown and hidden', async () => {
        const wrapper = mockFormControlWrapper();

        expect(wrapper.findComponent('.spinner-border').exists()).toBe(false);

        await wrapper.setProps({loading: true});
        expect(wrapper.findComponent('.spinner-border').exists()).toBe(true);
    });

    it('Check whether the class is displayed or not', async () => {
        const wrapper = mockFormControlWrapper();

        expect(wrapper.findComponent('.pe-all').exists()).toBe(false);

        await wrapper.setProps({labelPointerEvents: true});
        expect(wrapper.findComponent('.pe-all').exists()).toBe(true);
    });
});
