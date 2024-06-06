import {shallowMount, createLocalVue} from '@vue/test-utils';
import MDropdown from '../../js/components/UI/Dropdown.vue';

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
function mockMDropdown(props = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(MDropdown, {
        localVue: localVue,
        propsData: {
            text: 'textTest',
            type: 'primary',
            loading: false,
            hideAssistive: false,
            theme: '',
            ...props,
        },
    });

    return wrapper;
}

describe('MDropdown', () => {
    let formControlFieldClassTest;

    beforeEach(() => {
        formControlFieldClassTest = {
            'disabled': false,
            'has-postfix-icon': false,
            'invalid': false,
        };
    });

    it('Verify that "formControlFieldClass" works correctly', async () => {
        const wrapper = mockMDropdown();

        expect(wrapper.vm.formControlFieldClass).toEqual(formControlFieldClassTest);
    });

    it('Check "formControlFieldClass" when the prop "theme" is "white"', async () => {
        const wrapper = mockMDropdown();

        await wrapper.setProps({theme: 'white'});
        formControlFieldClassTest[wrapper.vm.theme + '-theme'] = true;

        expect(wrapper.vm.formControlFieldClass).toEqual(formControlFieldClassTest);
    });

    it('Verify that the spinner is shown and hidden', async () => {
        const wrapper = mockMDropdown();

        expect(wrapper.findComponent('.spinner-border').exists()).toBe(false);

        await wrapper.setProps({loading: true});
        expect(wrapper.findComponent('.spinner-border').exists()).toBe(true);
    });
});
