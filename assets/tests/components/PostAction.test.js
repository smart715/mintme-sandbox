import {shallowMount, createLocalVue} from '@vue/test-utils';
import '../__mocks__/ResizeObserver';
import PostActions from '../../js/components/posts/PostActions';
import Vuex from 'vuex';
import user from '../../js/storage/modules/user';
import posts from '../../js/storage/modules/posts';
import moment from 'moment';
import axios from 'axios';
import moxios from 'moxios';

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
    createdAt: moment().subtract(2, 'days').format(),
    isUserAlreadyRewarded: false,
    title: 'Test',
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
    shareReward: '0',
};

describe('PostActions', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('sharePost works correctly', async () => {
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
                posts: {
                    ...posts,
                    state: {
                        postRewardsCollectableDays: 30,
                        isAuthorizedForReward: false,
                    },
                },
                tradeBalance: {
                    namespaced: true,
                    mutations: {
                        setQuoteFullBalance: () => {
                        },
                    },
                },
            },
        });
        const wrapper = shallowMount(PostActions, {
            localVue,
            propsData: {},
            store,
        });

        // no reward, should open web intent pop up (not any modal)
        wrapper.vm.sharePost(testPost);
        expect(wrapper.vm.showLoginModal).toBe(false);
        expect(wrapper.vm.showTwitterSignInModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(false);
        expect(wrapper.vm.showNoPhoneNumberModal).toBe(false);

        // reward set, but user not logged in, should show login modal
        await wrapper.setProps({
            loggedIn: false,
        });

        wrapper.vm.sharePost({
            ...testPost,
            shareReward: '1',
        });
        expect(wrapper.vm.showLoginModal).toBe(true);
        expect(wrapper.vm.showTwitterSignInModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(false);
        expect(wrapper.vm.showNoPhoneNumberModal).toBe(false);

        wrapper.vm.showLoginModal = false;

        // reward set, user logged in, but does not have phone number confirmed, should show no phone number modal

        await wrapper.setProps({loggedIn: true});

        wrapper.vm.sharePost({
            ...testPost,
            shareReward: '1',
        });
        expect(wrapper.vm.showLoginModal).toBe(false);
        expect(wrapper.vm.showTwitterSignInModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(false);
        expect(wrapper.vm.showNoPhoneNumberModal).toBe(true);

        wrapper.vm.showNoPhoneNumberModal = false;

        // reward set, user logged in, has phone number verified,
        // but not signed in with twitter, should show twitter sign in modal

        store.commit('posts/setIsAuthorizedForReward', true);

        wrapper.vm.sharePost({
            ...testPost,
            shareReward: '1',
        });
        expect(wrapper.vm.showLoginModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(false);
        expect(wrapper.vm.showNoPhoneNumberModal).toBe(false);
        expect(wrapper.vm.showTwitterSignInModal).toBe(true);

        wrapper.vm.showTwitterSignInModal = false;

        // reward set, user logged in, has phone number verified, signed in on twitter, should show confirm share modal

        store.commit('user/setIsSignedInWithTwitter', true);

        wrapper.vm.sharePost({
            ...testPost,
            shareReward: '1',
        });
        expect(wrapper.vm.showLoginModal).toBe(false);
        expect(wrapper.vm.showTwitterSignInModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(true);
        expect(wrapper.vm.showNoPhoneNumberModal).toBe(false);

        wrapper.vm.showConfirmShareModal = false;

        // reward set, user is owner of token, should use web intent

        store.commit('user/setId', 1);

        wrapper.vm.sharePost({
            ...testPost,
            shareReward: '1',
        });
        expect(wrapper.vm.showLoginModal).toBe(false);
        expect(wrapper.vm.showTwitterSignInModal).toBe(false);
        expect(wrapper.vm.showNoPhoneNumberModal).toBe(false);
        expect(wrapper.vm.showConfirmShareModal).toBe(true);
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
                posts: {
                    ...posts,
                    state: {
                        postRewardsCollectableDays: 30,
                        isAuthorizedForReward: false,
                    },
                },
                tradeBalance: {
                    namespaced: true,
                    mutations: {
                        setQuoteFullBalance: () => {},
                    },
                },
            },
        });
        const wrapper = shallowMount(PostActions, {
            localVue,
            propsData: {
                post: testPost,
            },
            store,
        });
        wrapper.vm.activeModalPost = {
            ...testPost,
            shareReward: '1',
        };

        it('on success', (done) => {
            moxios.stubRequest('share_post', {
                status: 202,
                response: {
                    balance: '1',
                },
            });

            wrapper.vm.doSharePost();

            moxios.wait(() => {
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
});
