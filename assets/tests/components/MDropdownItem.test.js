import {shallowMount, createLocalVue} from '@vue/test-utils';
import MDropdownItem from '../../js/components/UI/DropdownItem.vue';

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
function mockMDropdownItem(props = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(MDropdownItem, {
        localVue: localVue,
        propsData: {
            value: '',
            active: false,
            ...props,
        },
    });

    return wrapper;
}

describe('MDropdownItem', () => {
    it('Verify that "itemClass" works correctly', () => {
        const wrapper = mockMDropdownItem();

        expect(wrapper.vm.itemClass).toEqual([]);
    });

    it('Check "itemClass" when the prop "active" is "true"', async () => {
        const wrapper = mockMDropdownItem();

        await wrapper.setProps({active: true});

        expect(wrapper.vm.itemClass).toEqual(['active']);
    });

    it('Verify that event "click" is emitted', async () => {
        const wrapper = mockMDropdownItem();
        const dropdownItem = wrapper.findComponent({ref: 'dropdownItem'});

        await dropdownItem.vm.$listeners.click();

        expect(wrapper.emitted().click).toBeTruthy();
    });
});
