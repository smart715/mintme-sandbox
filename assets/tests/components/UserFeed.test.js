import {shallowMount, createLocalVue} from '@vue/test-utils';
import '../__mocks__/ResizeObserver';
import UserFeed from '../../js/components/posts/UserFeed';
import Vuex from 'vuex';
import axios from 'axios';
import moxios from 'moxios';

const localVue = mockVue();

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
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createRecentPostsAndCommentsProps(props = {}) {
    return {
        isAuthorizedForReward: false,
        loggedIn: true,
        viewOnly: false,
        ...props,
    };
};

/**
 * @param {Object} mutations
 * @param {Object} state
 * @param {Object} getters
 * @return {Vuex.Store}
 */
function createRecentPostsAndCommentsStore(mutations, state, getters) {
    return new Vuex.Store({
        modules: {
            posts: {
                mutations,
                state,
                namespaced: true,
                getters,
            },
        },
    });
};

const testPost = {
    'id': 3,
    'content': null,
    'createdAt': '2023-01-10T18:57:59+00:00',
    'token': {
        'name': 'SUPERTOKEN',
        'cryptoSymbol': 'WEB',
    },
    'amount': 10.000000000000,
    'title': 'Title',
    'shareReward': 10.000000000000,
    'slug': 'title-slug',
    'likes': 0,
    'status': 1,
    'author': {
        'nickname': 'Elon Musk',
    },
    'commentsCount': 0,
    'isUserAlreadyRewarded': false,
    'isUserAlreadyLiked': false,
};

const testComment = {
    'id': 1,
    'content': 'test',
    'createdAt': '2023-01-01T13:25:41+00:00',
    'updatedAt': null,
    'author': {},
    'likeCount': 0,
    'tips': [],
    'editable': false,
    'deletable': false,
    'liked': false,
    'tipped': false,
};

describe('RecentPostsAndComments', () => {
    let wrapper;
    let mutations;
    let getters;
    let store;
    let state;

    beforeEach(() => {
        mutations = {
            setPostRewardsCollectableDays: jest.fn(),
            setIsAuthorizedForReward: jest.fn(),
            setCommentTipCost: jest.fn(),
            setCommentTipMinAmount: jest.fn(),
            setCommentTipMaxAmount: jest.fn(),
            setComments: jest.fn(),
        };

        getters = {
            getPosts: () => jest.fn(),
        };

        state = {
            postRewardsCollectableDays: 30,
            isAuthorizedForReward: false,
        };

        store = createRecentPostsAndCommentsStore(mutations, state, getters);

        wrapper = shallowMount(UserFeed, {
            localVue: localVue,
            sync: false,
            store: store,
            propsData: createRecentPostsAndCommentsProps(),
            directives: {
                'html-sanitize': () => {},
            },
            data() {
                return {
                    posts: [testPost],
                    comments: [testComment],
                    items: [testPost, testComment],
                };
            },
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Shouldn\'t display the items', async () => {
        await wrapper.setData({items: []});

        expect(wrapper.vm.hasItems).toBe(false);
    });

    it('Should display the items', async () => {
        await wrapper.setData({items: [testPost, testComment]});

        expect(wrapper.vm.hasItems).toBe(true);
    });

    it('Should return posts and comments if request ok', async () => {
        moxios.stubRequest('recent_posts_and_comments', {
            status: 200,
            response: {
                posts: [testPost],
                comments: [testComment],
            },
        });

        await wrapper.vm.fetchRecentPostsAndComments();

        expect(wrapper.vm.posts[0]).toBe(testPost);
        expect(wrapper.vm.comments[0]).toBe(testComment);
        expect(wrapper.vm.hasMoreItems).toBe(false);
    });

    it('shows error if fetch posts failed', async () => {
        const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError').mockImplementation();

        moxios.stubRequest('recent_posts_and_comments', {
            status: 500,
            response: {
                error: 'error',
            },
        });

        await wrapper.vm.fetchRecentPostsAndComments();

        expect(notifyErrorSpy).toHaveBeenCalled();
    });

    it('Should update post', () => {
        wrapper.vm.updatePost(testPost, 0);

        expect(wrapper.vm.posts[0]).toEqual(testPost);
    });

    it('Should open post', () => {
        const spyRoute = jest.spyOn(wrapper.vm.$routing, 'generate');
        jest.spyOn(console, 'error').mockImplementation();

        wrapper.vm.openPost(testPost);

        expect(spyRoute).toHaveBeenCalledWith('token_show_post', {
            name: testPost.token.name,
            slug: testPost.slug,
        }, true);
    });

    it('should not save like if user is already liked', () => {
        const post = wrapper.vm.posts[0];
        const initialLikes = post.likes;

        wrapper.vm.onSaveLike(post);

        expect(post.likes).toEqual(initialLikes + 1);
    });

    it('should save like if user is not already liked', () => {
        const post = wrapper.vm.posts[0];
        const initialLikes = post.likes;

        wrapper.vm.onSaveLike(post);

        expect(post.likes).toEqual(initialLikes - 1);
    });

    it('should return translations context', () => {
        wrapper.vm.$routing.generate = jest.fn().mockReturnValue('trading');

        expect(wrapper.vm.translationsContext).toEqual({'tradingUrl': 'trading'});
    });

    it('should return no recent posts message', () => {
        expect(wrapper.vm.noRecentPostsAndCommentsMessage).toEqual('page.pair.no_recent_feed');
    });

    it('should remove scroll event listener on destroy', () => {
        const spy = jest.spyOn(window, 'removeEventListener');

        wrapper.destroy();

        expect(spy).toHaveBeenCalledWith('scroll', wrapper.vm.handleDebouncedScroll);
    });
});
