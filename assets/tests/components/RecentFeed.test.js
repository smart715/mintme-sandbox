import {createLocalVue, shallowMount} from '@vue/test-utils';
import RecentFeed from '../../js/components/posts/RecentFeed';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: () => {}};
        },
    });

    return localVue;
}


/**
 * @return {Wrapper<Vuex.Store>}
 */
function mockStore() {
    return new Vuex.Store({
        modules: {
            posts: {
                mutations: {
                    setPostRewardsCollectableDays: jest.fn(),
                    setIsAuthorizedForReward: jest.fn(),
                    setCommentTipMinAmount: jest.fn(),
                    setComments: jest.fn(),
                },
                namespaced: true,
            },
        },
    });
}

/**
 * @return {Wrapper<Vue>}
 */
function createWrapper() {
    const localVue = mockVue();

    return shallowMount(RecentFeed, {
        store: mockStore(),
        localVue,
        directives: {
            'html-sanitize': () => {
            },
        },
    });
}

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

describe('RecentFeed', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = createWrapper();
    });

    describe('hasItems', () => {
        it('returns false when no items', () => {
            wrapper.setData({items: []});

            expect(wrapper.vm.hasItems).toBe(false);
        });

        it('returns true when there are items', () => {
            wrapper.setData({items: [{}]});

            expect(wrapper.vm.hasItems).toBe(true);
        });
    });

    describe('hasDeployedTokens', () => {
        it('returns false when no deployed tokens', async () => {
            await wrapper.setProps({ownDeployedTokens: []});

            expect(wrapper.vm.hasDeployedTokens).toBe(false);
        });

        it('returns true when deployed tokens', async () => {
            await wrapper.setProps({ownDeployedTokens: [{}]});

            expect(wrapper.vm.hasDeployedTokens).toBe(true);
        });
    });

    describe('offset', () => {
        it('returns max if showMore is true', async () => {
            await wrapper.setProps({showMore: true, max: 10});

            expect(wrapper.vm.offset).toBe(10);
        });

        it('returns min if showMore is false', async () => {
            await wrapper.setProps({showMore: false, min: 5});

            expect(wrapper.vm.offset).toBe(5);
        });
    });

    describe('itemsToShow', () => {
        it('shows min items when offset is min', async () => {
            await wrapper.setData({items: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]});
            await wrapper.setProps({showMore: false, min: 3});

            expect(wrapper.vm.itemsToShow).toEqual([1, 2, 3]);
        });

        it('shows max items when offset is max', async () => {
            await wrapper.setData({items: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]});
            await wrapper.setProps({showMore: true, max: 5});

            expect(wrapper.vm.itemsToShow).toEqual([1, 2, 3, 4, 5]);
        });
    });

    describe('fetchRecentPostsAndComments', () => {
        it('call notifyError if request fail', (done) => {
            jest.spyOn(axios.CancelToken, 'source').mockReturnValue({token: 'mockToken', cancel: jest.fn()});
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('recent_posts_and_comments', {
                status: 500,
            });

            wrapper.vm.fetchRecentPostsAndComments();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('proceedResponse', () => {
        it('sets the posts and comments', () => {
            wrapper.vm.proceedResponse({posts: [testPost], comments: [testComment]});

            expect(wrapper.vm.posts).toEqual([testPost]);
            expect(wrapper.vm.comments).toEqual([testComment]);
        });

        it('sort items by createdAt', () => {
            wrapper.vm.proceedResponse({
                posts: [
                    {createdAt: '2023-01-10T18:57:59+00:00'},
                    {createdAt: '2023-01-10T18:57:59+00:00'},
                ],
                comments: [
                    {createdAt: '2023-01-01T13:25:41+00:00'},
                    {createdAt: '2023-01-01T13:25:41+00:00'},
                ],
            });

            expect(wrapper.vm.items).toEqual([
                {createdAt: '2023-01-10T18:57:59+00:00'},
                {createdAt: '2023-01-10T18:57:59+00:00'},
                {createdAt: '2023-01-01T13:25:41+00:00'},
                {createdAt: '2023-01-01T13:25:41+00:00'},
            ]);
        });
    });

    describe('openPost', () => {
        const originalLocation = window.location;

        beforeAll(() => {
            Object.defineProperty(window, 'location', {
                value: {
                    href: 'https://www.mintme.com/',
                },
                configurable: true,
            });
        });

        afterAll(() => {
            Object.defineProperty(window, 'location', originalLocation);
        });

        it('opens the post whitout comment hash', () => {
            wrapper.vm.openPost(testPost);
            expect(window.location.href).toEqual('token_show_post');
        });

        it('opens the post with comment hash', () => {
            wrapper.vm.openPost(testPost, {id: 2});
            expect(window.location.href).toEqual('show_post#comment-2');
        });
    });

    describe('onSaveLike', () => {
        it('saves the like', () => {
            const post = {
                id: 1,
                likes: 0,
                isUserAlreadyLiked: false,
            };

            wrapper.vm.onSaveLike(post);

            expect(post.likes).toBe(1);
            expect(post.isUserAlreadyLiked).toBe(true);
        });

        it('removes the like', () => {
            const post = {
                id: 1,
                likes: 1,
                isUserAlreadyLiked: true,
            };

            wrapper.vm.onSaveLike(post);

            expect(post.likes).toBe(0);
            expect(post.isUserAlreadyLiked).toBe(false);
        });
    });

    describe('onCommentTip', () => {
        it('sets the activeTipComment and tipModalVisible to true', () => {
            wrapper.vm.onCommentTip(testComment);

            expect(wrapper.vm.activeTipComment).toEqual(testComment);
            expect(wrapper.vm.tipModalVisible).toBe(true);
        });
    });
});
