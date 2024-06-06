import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingCreate from '../../js/components/voting/VotingCreate';
import voting from '../../js/storage/modules/voting';
import {NotificationMixin} from '../../js/mixins';
import Vuex from 'vuex';
import moxios from 'moxios';
import axios from 'axios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
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
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: (val, params) => val, success: (val, params) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} store
 * @return {Wrapper<Vue>}
 */
function mockVotingCreate(props = {}, store = {}) {
    return shallowMount(VotingCreate, {
        localVue,
        store: createSharedTestStore(store),
        propsData: createSharedTestProps(props),
        directives: {
            'b-tooltip': {},
        },
    });
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        isTokenPage: false,
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
                ...store,
            },
        },
    });
}

const votingTest = {
    title: 'jasm-voting',
    id: '22',
    slug: 'slug-voting',
};

describe('CreateVoting', () => {
    const url = '/voting/';

    Object.defineProperty(window, 'location', {
        value: {
            href: url,
        },
        configurable: true,
    });

    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('publish button', () => {
        it('publish button is enabled if form, options is requesting is false', async () => {
            const wrapper = mockVotingCreate({}, {
                getters: {
                    getInvalidOptions: () => false,
                    getInvalidForm: () => false,
                },
            });

            await wrapper.setData({
                requesting: false,
            });

            expect(wrapper.vm.disabledPublish).toBe(false);
        });

        it('publish button is disabled if form is invalid', async () => {
            const wrapper = mockVotingCreate({}, {
                getters: {
                    getInvalidOptions: () => false,
                    getInvalidForm: () => true,
                },
            });

            await wrapper.setData({
                requesting: false,
            });

            expect(wrapper.vm.disabledPublish).toBe(true);
        });

        it('publish button is disabled if options is invalid', async () => {
            const wrapper = mockVotingCreate({}, {
                getters: {
                    getInvalidOptions: () => true,
                    getInvalidForm: () => false,
                },
            });

            await wrapper.setData({
                requesting: false,
            });

            expect(wrapper.vm.disabledPublish).toBe(true);
        });

        it('publish button is disabled if form is valid but request is pending', async () => {
            const wrapper = mockVotingCreate({}, {
                getters: {
                    getInvalidOptions: () => false,
                    getInvalidForm: () => false,
                },
            });

            await wrapper.setData({
                requesting: true,
            });

            expect(wrapper.vm.disabledPublish).toBe(true);
        });
    });

    describe('Verify that "redirectToShowVotings" works correctly', () => {
        it('When "isTokenPage" is true', async () => {
            const wrapper = mockVotingCreate();

            await wrapper.setProps({
                isTokenPage: true,
            });

            wrapper.vm.redirectToShowVotings(votingTest);

            expect(wrapper.emitted('voting-created')).toBeTruthy();
            expect(wrapper.emitted('voting-created')[0]).toEqual([votingTest]);
        });

        it('When "isTokenPage" is false', async () => {
            const wrapper = mockVotingCreate();

            await wrapper.setProps({
                isTokenPage: false,
            });

            wrapper.vm.redirectToShowVotings(votingTest);

            expect(wrapper.emitted('voting-created')).toBeFalsy();
            expect(window.location.href).toBe('show_voting');
        });
    });

    describe('Verify that "publish" works correctly', () => {
        it('publish success', async (done) => {
            const wrapper = mockVotingCreate({}, {
                getters: {
                    getTokenName: () => 'jasm-token',
                    getVotingData: () => votingTest,
                    getInvalidOptions: () => false,
                    getInvalidForm: () => false,
                },
            });

            wrapper.vm.notifySuccess = jest.fn();

            moxios.stubRequest('store_voting', {
                status: 200,
                response: {
                    data: votingTest,
                },
            });

            await wrapper.vm.publish();

            moxios.wait(() => {
                expect(wrapper.vm.notifySuccess).toHaveBeenCalledWith('voting.added_successfully');
                expect(wrapper.vm.requesting).toBe(false);
                done();
            });
        });

        it('publish denied', async (done) => {
            const wrapper = mockVotingCreate();

            wrapper.vm.notifyError = jest.fn();

            moxios.stubRequest('store_voting', {
                status: 403,
                response: {
                    message: 'error-message',
                },
            });

            await wrapper.vm.publish();

            moxios.wait(() => {
                expect(wrapper.vm.notifyError).toHaveBeenCalledWith('error-message');
                expect(wrapper.vm.requesting).toBe(false);
                done();
            });
        });
    });
});
