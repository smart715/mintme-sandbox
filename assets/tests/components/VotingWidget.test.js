import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingWidget from '../../js/components/voting/VotingWidget';
import voting from '../../js/storage/modules/voting';
import {NotificationMixin} from '../../js/mixins';
import Vuex from 'vuex';
import axios from 'axios';
import moxios from 'moxios';

const localVue = mockVue();

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.mixin(NotificationMixin);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$logger = {error: (val) => {}};
            Vue.prototype.$toasted = {show: () => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        tokenNameProp: 'foo',
        votingsProp: [],
        minAmount: 1,
        tokenAvatar: 'jasm-avatar',
        votingAmount: 22,
        minAmountPropose: 100,
        minAmountVote: 0,
        activePageProp: '',
        votingProp: {},
        loggedIn: false,
        ...props,
    };
}

/**
 * @param {Object} store
 * @return {Vuex.Store}
 */
function createSharedTestStore(store = {}) {
    return new Vuex.Store({
        modules: {
            voting: {
                ...voting,
                getters: {
                    getCurrentVoting: () => ({slug: 'show'}),
                },
            },
        },
    });
}

describe('VotingWidget', () => {
    let store;
    let wrapper;

    beforeEach(() => {
        store = createSharedTestStore();

        wrapper = shallowMount(VotingWidget, {
            localVue: localVue,
            store,
            propsData: createSharedTestProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
        wrapper.destroy();
    });

    describe('activePage', () => {
        it('should render list correctly', () => {
            expect(wrapper.vm.activePage).toEqual({
                list: true,
                create: false,
                show: false,
            });
        });

        it('should render create correctly', async () => {
            await wrapper.setData({
                activePageName: 'create_voting',
            });

            expect(wrapper.vm.activePage).toEqual({
                list: false,
                create: true,
                show: false,
            });
        });

        it('should render create correctly', async () => {
            await wrapper.setData({
                activePageName: 'show_voting',
            });

            expect(wrapper.vm.activePage).toEqual({
                list: false,
                create: false,
                show: true,
            });
        });
    });

    it('should go to create correctly', () => {
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
