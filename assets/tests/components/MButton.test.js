import {shallowMount, createLocalVue} from '@vue/test-utils';
import MButton from '../../js/components/UI/Button.vue';

const typesButton = {
    link: 'link',
    primary: 'primary',
};

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
function mockMButton(props = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(MButton, {
        localVue: localVue,
        propsData: {
            type: null,
            disabled: false,
            loading: false,
            wide: false,
            ...props,
        },
    });

    return wrapper;
}

describe('MButton', () => {
    let btnClassTest;

    beforeEach(() => {
        btnClassTest = {
            'btn-loading': false,
            'btn-wide': false,
            'disabled': false,
        };
    });

    it('Verify that "btnClass" works correctly', () => {
        const wrapper = mockMButton();

        expect(wrapper.vm.btnClass).toEqual(btnClassTest);
    });

    it('Check "btnClass" when the prop "type" is "primary"', async () => {
        const wrapper = mockMButton();

        await wrapper.setProps({type: typesButton.primary});
        btnClassTest['btn-' + wrapper.vm.type] = true;

        expect(wrapper.vm.btnClass).toEqual(btnClassTest);
    });

    it('Check "btnClass" when the prop "type" is "link"', async () => {
        const wrapper = mockMButton();

        await wrapper.setProps({type: typesButton.link});
        btnClassTest['btn-' + wrapper.vm.type] = true;

        expect(wrapper.vm.btnClass).toEqual(btnClassTest);
    });

    it('Verify that "btnContentClass" works correctly', async () => {
        const wrapper = mockMButton();

        expect(wrapper.vm.btnContentClass).toBe('');

        await wrapper.setProps({loading: true});

        expect(wrapper.vm.btnContentClass).toBe('opacity-0');
    });

    it('Verify that "onClick" works correctly', async () => {
        const wrapper = mockMButton();

        await wrapper.findComponent('button').trigger('click');

        expect(wrapper.emitted().click).toBeTruthy();
    });

    it('Verify that the spinner is shown and hidden', async () => {
        const wrapper = mockMButton();

        expect(wrapper.findComponent('.spinner-border').exists()).toBe(false);

        await wrapper.setProps({loading: true});
        expect(wrapper.findComponent('.spinner-border').exists()).toBe(true);
    });
});
