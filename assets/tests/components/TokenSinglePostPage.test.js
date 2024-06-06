import {shallowMount, createLocalVue} from '@vue/test-utils';
import Vuex from 'vuex';
import TokenSinglePostPage from '../../js/components/token/TokenSinglePostPage';
import postsModule from '../../js/storage/modules/posts';
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
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        initialPost: {
            token: {name: 'TOK'},
        },
        initialComments: [],
        ...props,
    };
}

/**
 * @param {Object} store
 * @return {Vuex.Store}
 */
function createSharedTestStore(store = {}) {
    return new Vuex.Store({
        modules: {
            posts: postsModule,
        },
        ...store,
    });
}

/**
 * @param {Object} props
 * @param {Object} store
 * @return {Wrapper<Vue>}
 */
function mockTokenSinglePostPage(props = {}, store = {}) {
    return shallowMount(TokenSinglePostPage, {
        localVue: localVue,
        store: createSharedTestStore(store),
        propsData: createSharedTestProps(props),
    });
}

describe('TokenSinglePostPage', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('do not load comments on first mount', () => {
        const wrapper = mockTokenSinglePostPage();
        const loadCommentsStub = jest.spyOn(wrapper.vm, 'loadComments');

        expect(loadCommentsStub).not.toHaveBeenCalled();
    });

    it('loads comments on post change', () => {
        const loadCommentsSpy = jest.spyOn(TokenSinglePostPage.methods, 'loadComments');

        shallowMount(TokenSinglePostPage, {
            store: new Vuex.Store({
                modules: {
                    posts: {
                        ...postsModule,
                        state: {
                            ...postsModule.state,
                            singlePosts: {test: true},
                        },
                    },
                },
            }),
            localVue,
            propsData: {
                initialPost: {
                    token: {name: 'TOK'},
                },
                initialComments: [],
            },
        });

        expect(loadCommentsSpy).toHaveBeenCalled();
    });

    it('Check that the loadComments method works correctly', async () => {
        const setCommentsFunc = jest.fn();
        const wrapper = shallowMount(TokenSinglePostPage, {
            localVue,
            propsData: {
                initialPost: {
                    token: {name: 'TOK'},
                },
                initialComments: [],
            },
            store: new Vuex.Store({
                modules: {
                    posts: {
                        ...postsModule,
                        state: {
                            ...postsModule.state,
                            singlePosts: {test: true},
                        },
                        mutations: {
                            setComments: setCommentsFunc,
                        },
                    },
                },
            }),
        });
        moxios.stubRequest('get_post_comments', {status: 200});

        wrapper.vm.loadComments();

        moxios.wait(async () => {
            expect(setCommentsFunc).toHaveBeenCalled();
            done();
        });
    });
});
