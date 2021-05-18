import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingOptions from '../../js/components/voting/VotingOptions';
import voting from '../../js/storage/modules/voting';
import Vuex from 'vuex';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} overrideVoting
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
    const wrapper = shallowMount(VotingOptions, {
        store,
        localVue,
    });

    return wrapper;
}

describe('VotingOptions', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('invalidOptions', () => {
        it('should be invalid in case any is empty', () => {
            const wrapper = createWrapper({
                getters: {
                    ...voting.getters,
                    getOptions: () => [
                        {title: 'foo'},
                        {title: ''},
                    ],
                },
            });
            expect(wrapper.vm.invalidOptions).toBe(true);
        });

        it('should be invalid in case any has errors', () => {
            const wrapper = createWrapper({
                getters: {
                    ...voting.getters,
                    getOptions: () => [
                        {title: 'foo'},
                        {
                            title: 'foo',
                            errorMessage: 'error',
                        },
                    ],
                },
            });
            expect(wrapper.vm.invalidOptions).toBe(true);
        });
    });

    it('should disable the btn in case invalid', () => {
        let wrapper = createWrapper();
        expect(wrapper.vm.disabledPublish).toBe(true);
        wrapper = createWrapper({
            getters: {
                ...voting.getters,
                getOptions: () => [{title: 'foo'}, {title: 'bar'}],
                getInvalidForm: () => false,
            },
        });
        expect(wrapper.vm.disabledPublish).toBe(false);
    });

    describe('publish', () => {
        it('should reset/unshift after submitting successfully', (done) => {
            let unshiftVotingCalled = false;
            let resetVotingDataCalled = false;
            const wrapper = createWrapper({
                actions: {
                    ...voting.actions,
                    unshiftVoting: () => unshiftVotingCalled = true,
                    resetVotingData: () => resetVotingDataCalled = true,
                },
            });
            wrapper.vm.publish();

            moxios.stubRequest('store_voting', {
                status: 200,
                response: {
                    data: {
                        voting: 'foo',
                    },
                },
            });

            moxios.wait(() => {
                expect(unshiftVotingCalled).toBe(true);
                expect(resetVotingDataCalled).toBe(true);
                done();
            });
        });
    });
});
