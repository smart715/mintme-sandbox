import {shallowMount, createLocalVue} from '@vue/test-utils';
import '../__mocks__/ResizeObserver';
import RecentPosts from '../../js/components/posts/RecentPosts';

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
        const wrapper = shallowMount(RecentPosts, {
            localVue,
        });

        expect(wrapper.vm.hasPosts).toBe(false);
    });

    it('shows posts if posts is not empty', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(RecentPosts, {
            localVue,
            propsData: {
                postsProp: [testPost],
            },
        });

        expect(wrapper.vm.hasPosts).toBe(true);
    });
});
