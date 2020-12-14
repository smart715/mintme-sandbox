import {shallowMount, createLocalVue} from '@vue/test-utils';
import Comments from '../../js/components/posts/Comments';

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

const testComment = {
    id: 1,
    amount: '0',
    content: 'foo',
    createdAt: '2016-01-01T23:35:01',
    updatedAt: '2016-01-01T23:35:01',
    editable: false,
    likeCount: 0,
    liked: false,
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
};

describe('Comments', () => {
    it('shows no one commented yet if there are no comments', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Comments, {
            localVue,
            propsData: {
                comments: [],
                postId: 1,
            },
        });

        expect(wrapper.findAll('comment-stub').length).toBe(0);
        expect(wrapper.find('.comments').html()).toContain('post.no_one_commented');
    });

    it('shows comments if comments is not empty', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Comments, {
            localVue,
            propsData: {
                comments: [testComment],
                postId: 1,
            },
        });

        expect(wrapper.find('comment-stub').exists()).toBe(true);
    });
});
