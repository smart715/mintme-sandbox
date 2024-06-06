import {createLocalVue, shallowMount} from '@vue/test-utils';
import axios from 'axios';
import moxios from 'moxios';
import TokenFollowButton from '../../js/components/token/TokenFollowButton';
import Vuex from 'vuex';
import {HTTP_OK} from '../../js/utils/constants';

Object.defineProperty(window, 'EventSource', {
    value: jest.fn(),
});

const testTokenName = 'testTokenName';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$toasted = {show: () => false};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
            Vue.prototype.$axios = {
                single: axios,
                retry: {get: axios},
            };
            Vue.prototype.$routing = {generate: (val, params = {}) => {
                Object.values(params).forEach((element) => {
                    val += '/' + element;
                });

                return val;
            }};
        },

    });
    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} computed
 * @return {Wrapper<Vue>}
 */
function mockTokenFollowButton(props = {}, computed = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {
            user: {
                namespaced: true,
                getters: {
                    getId() {
                        return null;
                    },
                },
            },
        },
    });

    return shallowMount(TokenFollowButton, {
        localVue,
        store,
        propsData: {
            tokenName: testTokenName,
            followerProp: false,
            mercureHubUrl: 'mercureHubTest',
            ...props,
        },
        computed: {
            ...computed,
        },
        directives: {
            'b-tooltip': {},
        },
    });
}

describe('TokenFollowButton', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('followToken method should send follow request if user is loggedIn', async () => {
        const props = {
            followerProp: false,
        };
        const computed = {
            userLoggedIn: function() {
                return true;
            },
        };
        const wrapper = mockTokenFollowButton(props, computed);

        moxios.stubRequest('token_follow/' + testTokenName, {
            status: HTTP_OK,
            response: {
                message: 'Success follow',
            },
        });

        await wrapper.vm.followToken();
        expect(wrapper.vm.follower).toBe(true);
    });

    it('unfollowToken method should send unfollow request', async () => {
        const props = {
            followerProp: true,
        };
        const wrapper = mockTokenFollowButton(props);

        moxios.stubRequest('token_unfollow/' + testTokenName, {
            status: HTTP_OK,
            response: {
                message: 'Success unfollow',
            },
        });

        await wrapper.vm.unfollowToken();
        expect(wrapper.vm.follower).toBe(false);
    });

    describe('userLoggedIn computed', () => {
        it('should return false is userId === null', () => {
            const computed = {
                getUserId: function() {
                    return null;
                },
            };
            const wrapper = mockTokenFollowButton({}, computed);
            expect(wrapper.vm.userLoggedIn).toBe(false);
        });

        it('should return true is userId !== null', () => {
            const computed = {
                getUserId: function() {
                    return 2;
                },
            };
            const wrapper = mockTokenFollowButton({}, computed);
            expect(wrapper.vm.userLoggedIn).toBe(true);
        });
    });

    describe('tooltipConfig computed', () => {
        it('.disabled should be false if user isn\'t logged in', () => {
            const computed = {
                userLoggedIn: function() {
                    return false;
                },
            };
            const wrapper = mockTokenFollowButton({}, computed);
            expect(wrapper.vm.tooltipConfig.disabled).toBe(false);
        });

        it('.disabled should be true if user is logged in', () => {
            const computed = {
                userLoggedIn: function() {
                    return true;
                },
            };
            const wrapper = mockTokenFollowButton({}, computed);
            expect(wrapper.vm.tooltipConfig.disabled).toBe(true);
        });
    });
});
