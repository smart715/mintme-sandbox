import {shallowMount, createLocalVue} from '@vue/test-utils';
import Post from '../../js/components/posts/Post';
import axios from 'axios';
import moxios from 'moxios';
import Vuex from 'vuex';
import moment from 'moment';

const localVue = mockVue();

Object.defineProperty(window, 'open', {
    value: () => ({closed: true}),
});
Object.defineProperty(window, 'location', {
    value: () => ({href: ''}),
    writable: true,
});

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => false};
            Vue.prototype.$logger = {error: () => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createPostProps(props = {}) {
    return {
        post: testPost,
        index: null,
        showEdit: false,
        loggedIn: true,
        recentPost: false,
        isSinglePost: false,
        isOwner: false,
        viewOnly: false,
        redirect: false,
        ...props,
    };
}


/**
 * @param {Object} mutations
 * @return {Vuex.Store}
 * @param {Object} state
 */
function createSharedTestStore(mutations, state) {
    return new Vuex.Store({
        modules: {
            user: {
                mutations,
                state,
                namespaced: true,
            },
            posts: {
                mutations,
                state,
                namespaced: true,
                getters: {
                    getPostRewardsCollectableDays: () => 30,
                },
            },
        },
    });
}

const DEFAULT_TOKEN_AVATAR = require('../../img/default_token_avatar.svg');
const testPost = {
    id: 1,
    amount: '0',
    content: 'foo',
    title: 'Test',
    shareReward: '0',
    isUserAlreadyRewarded: false,
    createdAt: moment().subtract(2, 'days').format(),
    author: {
        firstName: 'John',
        lastName: 'Doe',
        page_url: 'testPageUrl',
        nickname: 'John',
        image: {avatar_small: ''},
    },
    token: {
        name: 'MySuperTokenForTesting',
        ownerId: 1,
        image: {
            url: DEFAULT_TOKEN_AVATAR,
        },
    },
};

describe('Post', () => {
    let wrapper;
    let mutations;
    let state;
    let store;

    beforeEach(() => {
        mutations = {
            setLoggedInUserId: jest.fn(),
            setUserNickname: jest.fn(),
            setPostRewardsCollectableDays: jest.fn(),
            setIsAuthorizedForReward: jest.fn(),
        };

        state = {
            loggedInUserId: null,
            userNickname: 'John',
            postRewardsCollectableDays: 30,
            isAuthorizedForReward: false,
        };

        store = createSharedTestStore(mutations, state);

        wrapper = shallowMount(Post, {
            localVue: localVue,
            propsData: createPostProps(),
            store: store,
            sync: true,
            directives: {
                'b-tooltip': {},
            },
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Should emit "go-to-post" event', () => {
        wrapper.vm.goToPost(testPost);
        expect(wrapper.emitted('go-to-post')).toBeTruthy();
    });

    it('should truncate token name', async () => {
        await wrapper.setData({
            tokenTruncateLength: 15,
        });

        expect(wrapper.vm.shouldTruncateTokenName).toBe(true);
    });

    it('should display modal when shouldTruncateTokenName its true', () => {
        expect(wrapper.vm.modalTooltip).toEqual({
            boundary: 'viewport',
            placement: 'bottom',
            title: 'MySuperTokenForTesting',
        });
    });

    it('should return null if shouldTruncateTokenName is false', async () => {
        await wrapper.setData({
            tokenTruncateLength: 15,
        });

        await wrapper.setProps({
            post: {
                ...testPost,
                token: {
                    name: 'shortName',
                },
            },
        });

        expect(wrapper.vm.modalTooltip).toBeNull();
    });

    it('should return other class if isSinglePost is false', () => {
        expect(wrapper.vm.avatarClass).toEqual('mr-4 ml-2 mt-2');
    });

    it('should return correct class for avatar', async () => {
        await wrapper.setProps({
            isSinglePost: true,
        });

        expect(wrapper.vm.avatarClass).toEqual('mr-4 ml-2');
    });

    it('shows edit and delete icons if showEdit is true', async () => {
        await wrapper.setProps({
            showEdit: true,
        });

        expect(wrapper.findComponent('.post-edit-icon').exists()).toBe(true);
        expect(wrapper.findComponent('.delete-icon').exists()).toBe(true);
    });

    it('doesnt show edit and delete icons if showEdit is false', () => {
        expect(wrapper.findComponent('.icon-edit').exists()).toBe(false);
        expect(wrapper.findComponent('.delete-icon').exists()).toBe(false);
    });

    it('redirect to trade page if redirect props is true', async () => {
        await wrapper.setProps({
            redirect: true,
        });

        wrapper.vm.goToTrade();
        await wrapper.vm.$nextTick();

        expect(window.location.href).toEqual(wrapper.vm.tokenTradeTabLink);
    });

    it('do not redirect to trade page if redirect props is false', async () => {
        wrapper.vm.goToTrade();

        await wrapper.vm.$nextTick();
        expect(wrapper.emitted()['go-to-trade'].length).toEqual(1);
        expect(wrapper.emitted()['go-to-trade'][0]).toEqual(['0']);
    });

    it('computes hasReward correctly', async () => {
        await wrapper.setProps({
            post: {
                ...testPost,
                shareReward: '1',
            },
        });

        expect(wrapper.vm.hasReward).toBe(true);
    });

    it('should return true if post height is greater than max allowed height', () => {
        const maxAllowedHeight = 575;

        expect(wrapper.vm.isMaxAllowedHeight(maxAllowedHeight)).toBe(false);
    });

    it('should set see more button on timeout', (done) => {
        wrapper.vm.setSeeMoreButton();

        setTimeout(() => {
            expect(wrapper.vm.showSeeMoreButton).toBe(false);
            done();
        }, 0);
    });

    it('should call saveLike', () => {
        wrapper.vm.toggleLike();

        expect(wrapper.emitted('save-like')).toBeTruthy();
    });

    it('should call location.href if user is not logged in', async () => {
        await wrapper.setProps({
            loggedIn: false,
        });

        wrapper.vm.toggleLike();

        expect(window.location.href).toEqual(wrapper.vm.$routing.generate('login', {}, true));
    });

    it('should call toggleLike with response ok', (done) => {
        moxios.stubRequest('like_post', {
            status: 200,
        });

        wrapper.vm.toggleLike();

        moxios.wait(() => {
            expect(wrapper.emitted('save-like')).toBeTruthy();
            done();
        });
    });

    it('should call toggleLike with response error', (done) => {
        wrapper.vm.$logger = {error: jest.fn()};

        moxios.stubRequest('like_post', {
            status: 400,
            response: {
                data: {
                    message: 'error',
                },
            },
        });

        wrapper.vm.toggleLike();

        moxios.wait(() => {
            expect(wrapper.vm.$logger.error).toHaveBeenCalled();
            done();
        });
    });
});
