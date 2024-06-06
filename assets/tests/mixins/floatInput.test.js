import Vue from 'vue';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import FloatInputMixin from '../../js/mixins/float_input';
import Vuelidate from 'vuelidate';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuelidate);

    return localVue;
};

describe('FloatInputMixin', function() {
    const dataProvider = ['2', '2.', '2.0', '2.2', '.2'];

    dataProvider.forEach((item) => {
        it(`should be the same as input`, () => {
            const Component = Vue.component('foo', {
                template: '<div></div>',
                mixins: [FloatInputMixin],
            });
            const wrapper = shallowMount(Component, {
                localVue: mockVue(),
            });
            expect(wrapper.vm.parseFloatInput(item)).toBe(item);
        });
    });

    it('return changed value when it is . character', () => {
        const Component = Vue.component('foo', {
            template: '<div></div>',
            mixins: [FloatInputMixin],
        });
        const wrapper = shallowMount(Component, {
            localVue: mockVue(),
        });

        expect(wrapper.vm.parseFloatInput('.')).toBe('0.');
    });
});
