import {shallowMount, createLocalVue} from '@vue/test-utils';
import '../__mocks__/ResizeObserver';
import Posts from '../../js/components/posts/Posts';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
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
    author: {
        firstName: 'John',
        lastName: 'Doe',
        page_url: 'testPageUrl',
        nickname: 'John',
    },
    token: {
        name: 'tok',
    },
};

describe('Post', () => {
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
        });

        expect(wrapper.vm.hasPosts).toBe(true);
        expect(wrapper.find('post-stub').exists()).toBe(true);
    });

    it('shows all posts if max is null', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost, testPost],
                tokenName: 'tok',
            },
        });

        expect(wrapper.findAll('post-stub').length).toBe(4);
    });

    it('computes postsCount correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost, testPost],
                tokenName: 'tok',
                max: 8,
            },
        });

        expect(wrapper.vm.postsCount).toBe(4);

        wrapper.setProps({max: 2});

        expect(wrapper.vm.postsCount).toBe(2);
    });

    it('computes showReadMore correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost, testPost],
                tokenName: 'tok',
            },
        });

        expect(wrapper.vm.showReadMore).toBe(false);

        wrapper.setProps({max: 2});
        expect(wrapper.vm.showReadMore).toBe(true);

        wrapper.setProps({max: 8});
        expect(wrapper.vm.showReadMore).toBe(false);
    });

    it('shows read more if posts length is more than max', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Posts, {
            localVue,
            propsData: {
                posts: [testPost, testPost, testPost, testPost],
                tokenName: 'tok',
                max: 2,
            },
        });

        expect(wrapper.find('a[href=\'token_show\']').exists()).toBe(true);
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

        expect(wrapper.find('a[href=\'token_show\']').exists()).toBe(false);

        wrapper.setProps({max: 8});

        expect(wrapper.find('a[href=\'token_show\']').exists()).toBe(false);
    });
});
