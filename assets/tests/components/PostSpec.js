import {shallowMount, createLocalVue} from '@vue/test-utils';
import Post from '../../js/components/posts/Post';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
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
    it('shows content if post.content is not null', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost,
                showEdit: false,
            },
        });

        expect(wrapper.find('bbcode-view-stub').html()).to.contain('foo');
    });

    it('doesnt show content if post.content is null', () => {
        const localVue = mockVue();
        const testPost2 = Object.assign({}, testPost);
        testPost2.content = null;

        const wrapper = shallowMount(Post, {
            localVue,
            propsData: {
                post: testPost2,
                showEdit: false,
            },
        });

        expect(wrapper.find('p').html()).to.contain('To see this post you need to have 0 tok in your balance. Visit trade page and create buy order to get required tokens.');
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

        expect(wrapper.find('.post-edit-icon').exists()).to.be.true;
        expect(wrapper.find('.delete-icon').exists()).to.be.true;
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

        expect(wrapper.find('.post-edit-icon').exists()).to.be.false;
        expect(wrapper.find('.delete-icon').exists()).to.be.false;
    });
});
