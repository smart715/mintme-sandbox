import {createLocalVue, shallowMount} from '@vue/test-utils';
import clearInputMixin from '../../js/mixins/clear_input';
import Vuelidate from 'vuelidate';
import Vue from 'vue';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuelidate);

    return localVue;
};

describe('clearInputMixin', function() {
    const Component = Vue.component('foo', {
        data() {
            return {
                message: 'test',
            };
        },
        mixins: [clearInputMixin],
        template: `
      <div>
        <input
          type="text"
          v-model="message"
          name="message"
        />
        <button
          type="button"
          @click="clearInput()">Cancel</button>
      </div>
    `,
    });
    const wrapper = shallowMount(Component, {
        localVue: mockVue(),
    });

    it('should work correctly when clearInput method invoked', () => {
        wrapper.vm.message = 'test';
        wrapper.vm.clearInput('message', '');
        expect(wrapper.vm.message).toHaveLength(0);
    });
});
