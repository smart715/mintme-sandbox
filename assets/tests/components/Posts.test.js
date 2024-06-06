import {shallowMount, createLocalVue} from '@vue/test-utils';
import '../__mocks__/ResizeObserver';
import Posts from '../../js/components/posts/Posts';
import {MButton} from '../../js/components/UI';
import moment from 'moment';
import axios from 'axios';
import moxios from 'moxios';
import posts from '../../js/storage/modules/posts';
import Vuex from 'vuex';

Object.defineProperty(window, 'open', {
    value: () => ({closed: true}),
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
            Vue.prototype.$store = new Vuex.Store({
                modules: {
                    posts: posts,
                },
            });
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

// skipped due to https://github.com/vuejs/vue/issues/10939
describe.skip('Post', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('shows nothing here if posts is empty', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                tokenName: 'tok',
            },
        });

        expect(wrapper.vm.hasPosts).toBe(false);
        expect(wrapper.findAll('post-stub').length).toBe(0);
        expect(wrapper.html()).toContain('post.not_any_post');
    });

    it('shows posts if posts is not empty', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost],
                tokenName: 'tok',
            },
            attachTo: document.body,
        });

        expect(wrapper.vm.hasPosts).toBe(true);
        expect(wrapper.findComponent('post-stub').exists()).toBe(true);
    });

    it('shows all posts if max is null', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost],
                tokenName: 'tok',
            },
        });

        expect(wrapper.findAll('post-stub').length).toBe(3);
    });

    it('computes postsAmount correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost, testPost],
                tokenName: 'tok',
                postsAmount: 20,
            },
        });

        expect(wrapper.vm.postsAmount).toBe(20);
    });

    it('computes showLoadMore correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost, testPost],
                tokenName: 'tok',
                postsAmount: 6,
            },
        });

        expect(wrapper.vm.loadedAllPosts).toBe(false);

        wrapper.vm.setPosts([testPost, testPost, testPost, testPost, testPost]);
        expect(wrapper.vm.loadedAllPosts).toBe(false);

        wrapper.vm.setPosts([testPost, testPost, testPost, testPost, testPost, testPost]);
        expect(wrapper.vm.loadedAllPosts).toBe(true);
    });

    it('shows read more if posts length is more than max', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost, testPost],
                tokenName: 'tok',
                postsAmount: 10,
            },
        });

        expect(wrapper.findComponent(MButton).exists()).toBe(true);
    });

    it('doesnt show read more if posts length is less than max or max is null', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost, testPost],
                tokenName: 'tok',
            },
        });

        expect(wrapper.findComponent('a[href=\'token_show_post\']').exists()).toBe(false);

        wrapper.setProps({max: 8});

        expect(wrapper.findComponent('a[href=\'token_show_post\']').exists()).toBe(false);
    });

    it('onEditPostSuccess update localPosts', () => {
        const testPost2 = {...testPost, id: 2};

        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            propsData: {
                posts: [testPost, testPost2],
            },
            localVue,
        });

        const changedPost2 = {...testPost2};
        changedPost2.title = 'test-title2';

        wrapper.vm.onEditPostSuccess(changedPost2);
        expect(wrapper.vm.localPosts[1]).toBe(changedPost2);
    });

    it('onEditPostSuccess return undefined if new post', () => {
        const testPost2 = {...testPost, id: 2};

        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            propsData: {
                posts: [testPost, testPost2],
            },
            localVue,
        });

        wrapper.vm.onEditPostSuccess(testPost2);
        expect(wrapper.vm.localPosts[1]).toBe(testPost2);
    });

    it('onDeletePostSuccess reduce post count', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost, testPost],
                tokenName: 'tok',
                postsAmount: 10,
            },
        });

        wrapper.vm.onDeletePostSuccess(testPost);
        expect(wrapper.vm.maxPostsAmount).toBe(9);
    });

    it('onCreatePostSuccess reduce post count', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                postsAmount: 1,
            },
        });

        const testPost2 = {...testPost, id: 2};

        wrapper.vm.onCreatePostSuccess(testPost2);
        expect(wrapper.vm.localPosts[0].id).toBe(2);
        expect(wrapper.vm.maxPostsAmount).toBe(2);
    });
});
