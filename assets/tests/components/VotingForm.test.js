import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingForm from '../../js/components/voting/VotingForm';
import voting from '../../js/storage/modules/voting';
import Vuex from 'vuex';
import Vuelidate from 'vuelidate';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use(Vuelidate);
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

/**
 * @return {Wrapper<Vue>}
 */
function createWrapper() {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {voting},
    });
    const wrapper = shallowMount(VotingForm, {
        store,
        localVue,
    });

    return wrapper;
}

describe('VotingForm', () => {
    describe('invalidTitleMessage', () => {
        it('should display correct min length message', () => {
            const wrapper = createWrapper();
            wrapper.vm.title = 'foo';
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.invalidTitle).toBe(true);
            expect(wrapper.vm.invalidTitleMessage).toBe('form.validation.title.min');
        });
    });

    describe('invalidDescriptionMessage', () => {
        it('should display correct required message', () => {
            const wrapper = createWrapper();
            wrapper.vm.description = '[b][/b]';
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.invalidDescription).toBe(true);
            expect(wrapper.vm.invalidDescriptionMessage).toBe('form.validation.description.required');
        });

        it('should display correct min length message', () => {
            const wrapper = createWrapper();
            wrapper.vm.description = 'foo';
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.invalidDescription).toBe(true);
            expect(wrapper.vm.invalidDescriptionMessage).toBe('form.validation.description.min');
        });

        it('should display correct max length message', () => {
            const wrapper = createWrapper();
            wrapper.vm.description = 'a'.repeat(1001);
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.invalidDescription).toBe(true);
            expect(wrapper.vm.invalidDescriptionMessage).toBe('form.validation.description.max');
        });
    });
});
