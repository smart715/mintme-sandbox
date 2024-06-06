import {shallowMount, createLocalVue} from '@vue/test-utils';
import FormControl from '../../js/components/UI/FormControl';
import Vue from 'vue';

/**
 * @param {Object} slots
 * @return {Wrapper<Vue>}
 */
function mockFormControl(slots = {}) {
    const Component = Vue.component('foo', {
        mixins: [FormControl],
        template: '<div></div>',
    });

    return shallowMount(Component, {
        localVue: createLocalVue(),
        slots,
    });
}

describe('FormControl', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mockFormControl();
    });

    describe('inputName', () => {
        it('should return input name', async () => {
            await wrapper.setProps({name: 'foo'});

            expect(wrapper.vm.inputName).toBe('foo');
        });

        it('should return label name if name is not set', async () => {
            await wrapper.setProps({label: 'Foo bar'});

            expect(wrapper.vm.inputName).toBe('foo_bar');
        });
    });

    describe('hasErrors', () => {
        it('should return true if vnode.tag is defined', () => {
            const wrapper = mockFormControl({errors: '<div></div>'});

            expect(wrapper.vm.hasErrors).toBe(true);
        });

        it('should return false if vnode.tag is not defined', () => {
            const wrapper = mockFormControl({errors: '<div></div>'});
            wrapper.vm.$slots.errors[0].tag = undefined;

            expect(wrapper.vm.hasErrors).toBe(false);
        });
    });

    describe('formControlFieldClass', () => {
        it('should return object with invalid, disabled and has-postfix-icon as true', async () => {
            await wrapper.setProps({invalid: true, disabled: true, loading: true});

            expect(wrapper.vm.formControlFieldClass).toEqual({
                'invalid': true,
                'disabled': true,
                'has-postfix-icon': true,
            });
        });

        it('should return object with invalid, disabled and has-postfix-icon as false', async () => {
            await wrapper.setProps({invalid: false, disabled: false, loading: false});

            expect(wrapper.vm.formControlFieldClass).toEqual({
                'invalid': false,
                'disabled': false,
                'has-postfix-icon': false,
            });
        });
    });
});
