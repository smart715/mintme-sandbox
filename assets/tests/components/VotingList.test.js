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
                    getQuoteBalance: () => 0,
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
                getQuoteBalance: () => 1,
            }
        );
        expect(wrapper.vm.disableNewBtn).toBe(false);
    });

    describe('goToCreate', () => {
        it('shouldn\'t emit go-to-create in case not has min value', () => {
            let wrapper = createWrapper(
                {
                    minAmount: 2,
                },
                {
                    getQuoteBalance: () => 1,
                }
            );
            wrapper.vm.goToCreate();
            expect(wrapper.emitted('go-to-create')).toBeFalsy();
        });

        it('should emit go-to-create in case not has at least min value', () => {
            let wrapper = createWrapper(
                {
                    minAmount: 2,
                },
                {
                    getQuoteBalance: () => 3,
                }
            );
            wrapper.vm.goToCreate();
            expect(wrapper.emitted('go-to-create').length).toBe(1);
        });
    });
});
