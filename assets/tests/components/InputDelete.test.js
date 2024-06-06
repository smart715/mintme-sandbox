import {shallowMount} from '@vue/test-utils';
import InputDelete from '../../js/components/InputDelete';


describe('InputDelete ', () => {
    it('should not be rendered if has not error', () => {
        const wrapper = shallowMount(InputDelete, {
            propsData: {
                hasInputError: false,
            },
        });
        expect(wrapper.findComponent('div').exists()).toBe(false);
    });

    it('should be rendered if has errors', () => {
        const wrapper = shallowMount(InputDelete, {
            propsData: {
                hasInputError: true,
            },
        });
        expect(wrapper.findComponent('div').exists()).toBe(true);
    });

    it('should emit "clear-input" when clicking on the element ', () => {
        const wrapper = shallowMount(InputDelete, {
            propsData: {
                hasInputError: true,
            },
        });
        wrapper.findComponent('span').trigger('click');
        expect(wrapper.emitted('clear-input').length).toBe(1);
    });
});

