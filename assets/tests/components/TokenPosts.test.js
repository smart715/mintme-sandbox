import {shallowMount, createLocalVue} from '@vue/test-utils';
import Vuex from 'vuex';
import TokenPosts from '../../js/components/token/TokenPosts';
import postsModule from '../../js/storage/modules/posts';
import axios from 'axios';

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
        },
    });
    return localVue;
}

const v = mockVue();
v.use(Vuex);

describe('TokenPosts', () => {
    it('initializes data on first mount', () => {
        const setIsRewardsInitializedStub = jest.fn();
        const localVue = mockVue();
        shallowMount(TokenPosts, {
            store: new Vuex.Store({
                modules: {
                    posts: postsModule,
                    pair: {
                        namespaced: true,
                        state: {
                            isPostsInitialized: false,
                        },
                        getters: {
                            getIsPostsInitialized(state) {
                                return state.isPostsInitialized;
                            },
                        },
                        mutations: {
                            setIsPostsInitialized: setIsRewardsInitializedStub,
                        },
                    },
                },
            }),
            localVue,
            propsData: {
                isOwner: false,
                posts: [],
                postsAmount: 0,
            },
        });

        expect(setIsRewardsInitializedStub).toHaveBeenCalled();
    });

    it('doesnt initialize data on second mount', () => {
        const setIsPostsInitializedStub = jest.fn();
        const localVue = mockVue();
        shallowMount(TokenPosts, {
            store: new Vuex.Store({
                modules: {
                    posts: postsModule,
                    pair: {
                        namespaced: true,
                        state: {
                            isPostsInitialized: true,
                        },
                        getters: {
                            getIsPostsInitialized(state) {
                                return state.isPostsInitialized;
                            },
                        },
                        mutations: {
                            setIsPostsInitialized: setIsPostsInitializedStub,
                        },
                    },
                },
            }),
            localVue,
            propsData: {
                isOwner: false,
                posts: [],
                postsAmount: 0,
            },
        });

        expect(setIsPostsInitializedStub).not.toHaveBeenCalled();
    });
});
