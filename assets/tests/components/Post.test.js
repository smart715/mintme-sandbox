import {shallowMount, createLocalVue} from '@vue/test-utils';
import Post from '../../js/components/posts/Post';
import axios from 'axios';
import moxios from 'moxios';
import Vuex from 'vuex';
import user from '../../js/storage/modules/user';

Object.defineProperty(window, 'open', {
    value: () => ({closed: true}),
});

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => false};
        },
    });
    return localVue;
}

const testPost = {
    id: 1,
    amount: '0',
    content: 'foo',
    title: 'Test',
    shareReward: '0',
    isUserAlreadyRewarded: false,
    createdAt: '2016-01-01T23:35:01',
    author: {
        firstName: 'John',
        lastName: 'Doe',
        page_url: 'testPageUrl',
        nickname: 'John',
        image: {avatar_small: ''},
    },
    token: {
        name: 'tok',
        ownerId: 1,
    },
};

describe('Post', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('shows content if post.content is not null', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost,
                showEdit: false,
                loggedIn: true,
            },
        });

        expect(wrapper.find('bbcode-view-stub').html()).toContain('foo');
    });

    it('shows message to go to trade to buy tokens if post.content is null and loggedIn is true', () => {
        const localVue = mockVue();
        const testPost2 = Object.assign({}, testPost);
        testPost2.content = null;

        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost2,
                showEdit: false,
                loggedIn: true,
            },
        });

        expect(wrapper.find('p').html()).toContain('post.logged_in.1 <a href="#">0 tok</a> post.logged_in.2');
    });

    it('shows edit and delete icons if showEdit is true', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost,
                showEdit: true,
            },
        });

        expect(wrapper.find('.post-edit-icon').exists()).toBe(true);
        expect(wrapper.find('.delete-icon').exists()).toBe(true);
    });

    it('doesnt show edit and delete icons if showEdit is false', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost,
                showEdit: false,
            },
        });

        expect(wrapper.find('.icon-edit').exists()).toBe(false);
        expect(wrapper.find('.delete-icon').exists()).toBe(false);
    });

    it('computes hasReward correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost,
            },
        });

        expect(wrapper.vm.hasReward).toBe(false);

        wrapper.setProps({
            post: {
                ...testPost,
                shareReward: '1',
            },
        });

        expect(wrapper.vm.hasReward).toBe(true);
    });

    it('computes shareText correctly', () => {
        const localVue = mockVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {
                user: {
                    ...user,
                    state: {
                        id: null,
                    },
                },
            },
        });
        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost,
            },
            store,
        });

        // No reward, user not already rewarded
        expect(wrapper.vm.shareText).toBe('post.share');

        wrapper.setProps({
            post: {
                ...testPost,
                shareReward: '1',
                isUserAlreadyRewarded: false,
            },
        });

        // Reward set, user not already rewarded
        expect(wrapper.vm.shareText).toBe('post.share.reward');

        wrapper.setProps({
            post: {
                ...testPost,
                shareReward: '1',
                isUserAlreadyRewarded: true,
            },
        });

        // Reward set, user already rewarded
        expect(wrapper.vm.shareText).toBe('post.share');
    });

    it('sharePost works correctly', () => {
        const localVue = mockVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {
                user: {
                    ...user,
                    state: {
                        isSignedInWithTwitter: false,
                        id: null,
                    },
                },
            },
        });
        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost,
            },
            store,
        });

        // no reward, should open web intent pop up (not any modal)
        wrapper.vm.sharePost();
        expect(wrapper.vm.showLoginModal).toBe(false);
        expect(wrapper.vm.showTwitterSignInModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(false);

        // reward set, but user not logged in, should show login modal
        wrapper.setProps({
            post: {
                ...testPost,
                shareReward: '1',
            },
            loggedIn: false,
        });

        wrapper.vm.sharePost();
        expect(wrapper.vm.showLoginModal).toBe(true);
        expect(wrapper.vm.showTwitterSignInModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(false);

        wrapper.vm.showLoginModal = false;

        // reward set, user logged in, but not signed in with twitter, should show twitter sign in modal

        wrapper.setProps({loggedIn: true});

        wrapper.vm.sharePost();
        expect(wrapper.vm.showLoginModal).toBe(false);
        expect(wrapper.vm.showTwitterSignInModal).toBe(true);
        expect(wrapper.vm.showConfirmShareModal).toBe(false);

        wrapper.vm.showTwitterSignInModal = false;

        // reward set, user logged in, signed in on twitter, should show confirm share modal

        store.commit('user/setIsSignedInWithTwitter', true);

        wrapper.vm.sharePost();
        expect(wrapper.vm.showLoginModal).toBe(false);
        expect(wrapper.vm.showTwitterSignInModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(true);

        wrapper.vm.showConfirmShareModal = false;

        // reward set, user is owner of token, should use web intent

        store.commit('user/setId', 1);

        wrapper.vm.sharePost();
        expect(wrapper.vm.showLoginModal).toBe(false);
        expect(wrapper.vm.showTwitterSignInModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(false);
    });

    describe('doSharePost works correctly', () => {
        const localVue = mockVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {
                user: {
                    ...user,
                    state: {
                        isSignedInWithTwitter: true,
                    },
                },
            },
        });
        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost,
            },
            store,
        });

        it('on success', (done) => {
            moxios.stubRequest('share_post', {status: 202});

            wrapper.vm.doSharePost();

            moxios.wait(() => {
                expect(wrapper.vm.post.isUserAlreadyRewarded).toBe(true);
                done();
            });
        });

        it('on invalid token', (done) => {
            moxios.stubRequest('share_post', {
                status: 400,
                response: {
                    message: 'invalid twitter token',
                },
            });

            wrapper.vm.doSharePost();

            moxios.wait(() => {
                expect(wrapper.vm.isSignedInWithTwitter).toBe(false);
                expect(wrapper.vm.showTwitterSignInModal).toBe(true);
                done();
            });
        });

        it('on not enough funds', (done) => {
            moxios.stubRequest('share_post', {
                status: 409,
                response: {
                    message: 'not enough funds',
                },
            });

            wrapper.vm.doSharePost();

            moxios.wait(() => {
                expect(wrapper.vm.showErrorModal).toBe(true);
                done();
            });
        });
    });

    it('uses h1 or h2 correctly depending on singlePage prop', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost,
                singlePage: false,
            },
        });

        expect(wrapper.find('h1.post-title').exists()).toBe(false);
        expect(wrapper.find('h2.post-title').exists()).toBe(true);

        wrapper.setProps({singlePage: true});

        expect(wrapper.find('h1.post-title').exists()).toBe(true);
        expect(wrapper.find('h2.post-title').exists()).toBe(false);
    });
});
