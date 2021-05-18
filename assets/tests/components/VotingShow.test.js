import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingShow from '../../js/components/voting/VotingShow';
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
 * @param {Object} overrideVoting
 * @return {Wrapper<Vue>}
 */
function createWrapper(props = {}, overrideVoting = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {
            voting: {
                ...voting,
                ...overrideVoting,
            },
        },
    });
    const wrapper = shallowMount(VotingShow, {
        store,
        localVue,
        propsData: props,
    });

    return wrapper;
}

describe('VotingShow', () => {
    it('should setCurrentVoting in case props not null', () => {
        let setCurrentVotingCalled = false;
        createWrapper(
            {
                votingProp: {
                    id: 0,
                    creatorProfile: {
                        nickname: 'foo',
                    },
                },
            },
            {
                getters: {
                    ...voting.getters,
                    getCurrentVoting: () => {
                        return {
                            id: 0,
                            creatorProfile: {
                                nickname: 'foo',
                            },
                        };
                    },
                },
                mutations: {
                    ...voting.mutations,
                    setCurrentVoting: () => setCurrentVotingCalled = true,
                },
            }
        );
        expect(setCurrentVotingCalled).toBe(false);

        createWrapper(
            {
                votingProp: {
                    id: 1,
                    creatorProfile: {
                        nickname: 'foo',
                    },
                },
            },
            {
                getters: {
                    ...voting.getters,
                    getCurrentVoting: () => {
                        return {
                            id: 0,
                            creatorProfile: {
                                nickname: 'foo',
                            },
                        };
                    },
                },
                mutations: {
                    ...voting.mutations,
                    setCurrentVoting: () => setCurrentVotingCalled = true,
                },
            }
        );
        expect(setCurrentVotingCalled).toBe(true);
    });
});
