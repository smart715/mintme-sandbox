import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingVotes from '../../js/components/voting/VotingVotes';
import voting from '../../js/storage/modules/voting';
import Vuex from 'vuex';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.component('b-table', {});
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$routing = {
                generate: (val, params) => [val, ...Object.keys(params), ...Object.values(params)].join('_'),
            };
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
                getters: {
                    ...voting.getters,
                    getTokenName() {
                        return 'baz';
                    },
                    getCurrentVoting() {
                        return {
                            userVotings: [
                                {
                                    user: {profile: {nickname: 'foo'}},
                                    option: {title: 'bar'},
                                    amountMoney: '100',
                                },
                            ],
                        };
                    },
                },
                ...overrideVoting,
            },
        },
    });
    const wrapper = shallowMount(VotingVotes, {
        store,
        localVue,
    });

    return wrapper;
}

describe('VotingVotes', () => {
    it('should generate profile url correctly', () => {
        const wrapper = createWrapper();
        expect(wrapper.vm.getProfileUrl('foo')).toBe('profile-view_nickname_foo');
    });

    it('should compute count correctly', () => {
        const wrapper = createWrapper({
            getters: {
                ...voting.getters,
                getCurrentVoting() {
                    return {
                        userVotings: [
                            {
                                user: {profile: {nickname: 'foo'}},
                                option: {title: 'bar'},
                                amountMoney: '100',
                            },
                            {
                                user: {profile: {nickname: 'foo2'}},
                                option: {title: 'bar2'},
                                amountMoney: '1002',
                            },
                        ],
                    };
                },
            },
        });
        expect(wrapper.vm.votesCount).toBe(2);
    });

    it('should compute votes correctly', () => {
        const wrapper = createWrapper();
        expect(wrapper.vm.votes).toEqual([
            {
                trader: 'foo',
                option: 'bar',
                amount: '100 baz',
            },
        ]);
    });
});
