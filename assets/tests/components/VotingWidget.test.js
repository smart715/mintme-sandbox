import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingWidget from '../../js/components/voting/VotingWidget';
import voting from '../../js/storage/modules/voting';
import Vuex from 'vuex';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} getters
 * @return {Wrapper<Vue>}
 */
function createWrapper(props = {}, getters = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {voting},
    });
    const wrapper = shallowMount(VotingWidget, {
        propsData: {
            tokenNameProp: 'foo',
            votingsProp: [],
            minAmount: 1,
            ...props,
        },
        store,
        localVue,
    });

    return wrapper;
}

describe('VotingWidget', () => {
   describe('activePage', () => {
       it('should render list correctly', () => {
           const wrapper = createWrapper();

           expect(wrapper.vm.activePage).toEqual({
               list: true,
               create: false,
               show: false,
           });
       });

       it('should render create correctly', () => {
           const wrapper = createWrapper({activePageProp: 'create_voting'});

           expect(wrapper.vm.activePage).toEqual({
               list: false,
               create: true,
               show: false,
           });
       });

       it('should render create correctly', () => {
           const wrapper = createWrapper({activePageProp: 'show_voting'});

           expect(wrapper.vm.activePage).toEqual({
               list: false,
               create: false,
               show: true,
           });
       });
   });

    it('should go to create correctly', () => {
        const wrapper = createWrapper();

        expect(wrapper.vm.activePage).toEqual({
            list: true,
            create: false,
            show: false,
        });

        wrapper.vm.goToCreateVoting();

        expect(wrapper.vm.activePage).toEqual({
            list: false,
            create: true,
            show: false,
        });
    });

    it('should go to show correctly', () => {
        const wrapper = createWrapper(
            {},
            {
                getCurrentVoting: () => {
                    return {slug: 'foo'};
                },
            },
        );

        expect(wrapper.vm.activePage).toEqual({
            list: true,
            create: false,
            show: false,
        });

        wrapper.vm.goToShowVoting();

        expect(wrapper.vm.activePage).toEqual({
            list: false,
            create: false,
            show: true,
        });
    });
});
