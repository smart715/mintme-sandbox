import {shallowMount, createLocalVue} from '@vue/test-utils';
import SinglePostStatus from '../../js/components/posts/SinglePostStatus';
import Vuex from 'vuex';
import axios from 'axios';
import moxios from 'moxios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

const testPost = {
    id: 1,
    amount: '0',
    content: 'foo',
    createdAt: '2016-01-01T23:35:01',
    updatedAt: '2016-01-01T23:35:01',
    editable: false,
    author: {
        id: 1,
        profile: {
            anonymous: false,
            city: null,
            country: 'testCountry',
            description: '',
            firstName: 'John',
            lastName: 'Doe',
            image: {
                avatar_small: 'testAvatarSmall',
                avatar_middle: 'testAvatarMiddle',
                avatar_large: 'testAvatarLarge',
            },
            page_url: 'testPageUrl',
            nickname: 'John',
        },
    },
    isUserAlreadyLiked: true,
    commentsCount: 5,
    likes: 10,
};

describe('SinglePostStatus', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('remove like for single post', () => {
        const localVue = mockVue();

        const store = new Vuex.Store({
            modules: {
                posts: {
                    namespaced: true,
                    getters: {
                        getSinglePost: () => testPost,
                    },
                    mutations: {
                        removeSinglePostLike: () => {
                            testPost.likes--;
                            testPost.isUserAlreadyLiked = false;
                        },
                    },
                },
            },
        });

        const wrapper = shallowMount(SinglePostStatus, {
            store,
            localVue,
            propsData: {
                isLoggedIn: true,
            },
            data() {
                return {
                    requesting: false,
                };
            },
        });

        wrapper.vm.likePost();
        expect(wrapper.vm.post.likes).toEqual(9);
        expect(wrapper.vm.post.isUserAlreadyLiked).toBeFalsy();
    });

    it('not loggedin user likes post and redirected to login page', () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {
                posts: {
                    namespaced: true,
                    getters: {
                        getSinglePost: () => testPost,
                    },
                    mutations: {
                        removeSinglePostLike: () => {
                            testPost.likes--;
                            testPost.isUserAlreadyLiked = false;
                        },
                    },
                },
            },
        });

        global.window = Object.create(window);

        Object.defineProperty(window, 'location', {
            value: {
                href: 'url',
            },
        });

        const wrapper = shallowMount(SinglePostStatus, {
            store,
            localVue,
            propsData: {
                isLoggedIn: false,
            },
            data() {
                return {
                    requesting: false,
                };
            },
        });

        wrapper.vm.likePost();

        expect(window.location.href).toBe('login');
    });

    it('go to single post page', () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {
                posts: {
                    namespaced: true,
                    getters: {
                        getSinglePost: () => testPost,
                    },
                    mutations: {
                        removeSinglePostLike: () => {
                            testPost.likes--;
                            testPost.isUserAlreadyLiked = false;
                        },
                    },
                },
            },
        });

        global.window = Object.create(window);
        Object.defineProperty(window, 'location', {
            value: {
                href: 'url',
            },
        });

        const wrapper = shallowMount(SinglePostStatus, {
            store,
            localVue,
            propsData: {
                isLoggedIn: true,
            },
            data() {
                return {
                    requesting: false,
                };
            },
            computed: {
                singlePageUrl() {
                    return 'single_page_url';
                },
            },
        });

        wrapper.vm.goToSinglePost();
        expect(window.location.href).toBe('single_page_url');
    });
});
