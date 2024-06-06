import {shallowMount, createLocalVue} from '@vue/test-utils';
import FormControlCounter from '../../js/components/UI/FormControlCounter';
import Vue from 'vue';

/**
 * @return {Wrapper<Vue>}
 */
function mockFormControlCounter() {
    const Component = Vue.component('foo', {
        mixins: [FormControlCounter],
        template: '<div></div>',
    });

    return shallowMount(Component, {
        localVue: createLocalVue(),
    });
}

describe('FormControlCounter', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mockFormControlCounter();
    });

    describe('valueLength', () => {
        it('should return 0 if counter is false', () => {
            expect(wrapper.vm.valueLength).toBe(0);
        });

        it('should return 0 if value is empty', async () => {
            wrapper.setProps({counter: true, value: ''});

            expect(wrapper.vm.valueLength).toBe(0);
        });

        it('should return value length if value is string', async () => {
            await wrapper.setProps({counter: true, value: 'foo'});

            expect(wrapper.vm.valueLength).toBe(3);
        });

        it('should return value length if value is number', async () => {
            await wrapper.setProps({counter: true, value: 123});

            expect(wrapper.vm.valueLength).toBe(3);
        });
    });
});
