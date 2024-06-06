import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingOption from '../../js/components/voting/VotingOption';
import voting from '../../js/storage/modules/voting';
import Vuex from 'vuex';
import Vuelidate from 'vuelidate';
import {MInput} from '../../js/components/UI';

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
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function createWrapper(props = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {
            voting,
        },
    });
    const wrapper = shallowMount(VotingOption, {
        store,
        localVue,
        propsData: {
            option: {
                title: '',
                errorMessage: '',
            },
            ...props,
        },
    });

    return wrapper;
}

describe('VotingOption', () => {
    it('should emit update-option on input', () => {
        const wrapper = createWrapper();
        wrapper.findComponent(MInput).vm.$emit('input', 'foo');
        expect(wrapper.emitted('update-option')).toEqual([
            [
                {
                    title: 'foo',
                    errorMessage: '',
                },
            ],
        ]);
    });

    it('should validate max length correctly', () => {
        const wrapper = createWrapper({
            option: {
                title: 'a'.repeat(33),
            },
        });
        wrapper.vm.$v.$touch();
        wrapper.vm.validateOption();
        expect(wrapper.emitted('update-option')).toEqual([
            [
                {
                    title: 'a'.repeat(33),
                    errorMessage: 'form.validation.option.max',
                },
            ],
        ]);
    });
});
