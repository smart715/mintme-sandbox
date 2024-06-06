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
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {object} overrideVoting
 * @return {Wrapper<Vue>}
 */
function createWrapper(overrideVoting = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {
            voting: {
                ...voting,
                ...overrideVoting,
            },
        },
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
            const wrapper = createWrapper({
                getters: {
                    ...voting.getters,
                    ...{
                        getTitle: () => 'foo',
                    },
                },
            });
            expect(wrapper.vm.invalidTitle).toBe(true);
            expect(wrapper.vm.invalidTitleMessage).toBe('form.validation.title.min');
        });

        it('should display correct required if message is empty after trim', () => {
            const wrapper = createWrapper(
                {
                    getters: {
                        ...voting.getters,
                        ...{
                            getTitle: () => ' ',
                        },
                    },
                },
            );
            expect(wrapper.vm.invalidTitle).toBe(true);
            expect(wrapper.vm.invalidTitleMessage).toBe('form.validation.title.min');
        });

        it('should display correct max length message', () => {
            const wrapper = createWrapper({
                getters: {
                    ...voting.getters,
                    ...{
                        getTitle: () => 'f'.repeat(101),
                    },
                },
            });
            expect(wrapper.vm.invalidTitle).toBe(true);
            expect(wrapper.vm.invalidTitleMessage).toBe('form.validation.title.max');
        });

        it('shouldn\'t validate noBadWords if pathname isn\'t mintme voting', () => {
            delete window.location;
            window.location = {};
            window.location.pathname = '/foo';
            const wrapper = createWrapper({
                getters: {
                    ...voting.getters,
                    ...{
                        getTitle: () => 'foo bar',
                    },
                },
            });
            expect(wrapper.vm.invalidTitle).toBe(false);
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

        it('shouldn\'t validate noBadWords if pathname isn\'t mintme voting', () => {
            const wrapper = createWrapper();
            wrapper.vm.description = 'foo'.repeat(100);
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.invalidDescription).toBe(false);
        });
    });
});
