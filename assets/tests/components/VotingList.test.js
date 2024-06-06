import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingList from '../../js/components/voting/VotingList';
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
            Vue.prototype.$toasted = {show: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} tradeBalanceGetters
 * @return {Wrapper<Vue>}
 */
function createWrapper(props = {}, tradeBalanceGetters = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {
            voting,
            tradeBalance: {
                namespaced: true,
                getters: {
                    getQuoteFullBalance: () => 0,
                    getBalances: () => ({}),
                    isServiceUnavailable: () => false,
                    ...tradeBalanceGetters,
                },
            },
        },
    });
    const wrapper = shallowMount(VotingList, {
        store,
        localVue,
        propsData: {
            minAmount: 0,
            tokenNameProp: '',
            votingsProp: [],
            ...props,
        },
        directives: {
            'b-tooltip': {},
        },
    });

    return wrapper;
}

describe('VotingList', () => {
    it('should disable create btn till the balance loaded', () => {
        let wrapper = createWrapper();
        expect(wrapper.vm.disableNewBtn).toBe(true);
        wrapper = createWrapper(
            {},
            {
                getQuoteFullBalance: () => 1,
            }
        );
        expect(wrapper.vm.disableNewBtn).toBe(false);
    });

    it('Check if propositionTooltip returns the correct translation', async () => {
        const wrapper = createWrapper();

        await wrapper.setProps({isTokenPage: true});
        expect(wrapper.vm.propositionTooltip).toBe('voting.tooltip.page_token.proposition');

        await wrapper.setProps({isTokenPage: false});
        expect(wrapper.vm.propositionTooltip).toBe('voting.tooltip.propositions');
    });

    describe('goToCreate', () => {
        it('shouldn\'t emit go-to-create in case not has min value', () => {
            const wrapper = createWrapper(
                {
                    minAmount: 2,
                    loggedIn: true,
                },
                {
                    getQuoteFullBalance: () => 1,
                }
            );
            wrapper.vm.goToCreate();
            expect(wrapper.emitted('go-to-create')).toBeFalsy();
        });

        it('should emit go-to-create in case not has at least min value', () => {
            const wrapper = createWrapper(
                {
                    minAmount: 2,
                    loggedIn: true,
                },
                {
                    getQuoteFullBalance: () => 3,
                }
            );
            wrapper.vm.goToCreate();
            expect(wrapper.emitted('go-to-create').length).toBe(1);
        });
    });
});
