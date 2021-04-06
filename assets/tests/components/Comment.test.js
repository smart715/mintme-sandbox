import {shallowMount, createLocalVue} from '@vue/test-utils';
import Comment from '../../js/components/posts/Comment';
import axios from 'axios';
import moxios from 'moxios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
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
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('doesn\'t show delete and edit icons if editable is false', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Comment, {
            localVue,
            propsData: {
                comment: testComment,
            },
        });

        expect(wrapper.find('.delete-icon').exists()).toBe(false);
        expect(wrapper.find('.comment-edit-icon').exists()).toBe(false);
    });

    it('shows delete and edit icons if editable is true', () => {
        const localVue = mockVue();
        let comment = Object.assign({}, testComment);
        comment.editable = true;
        comment.deletable = true;
        const wrapper = shallowMount(Comment, {
            localVue,
            propsData: {
                comment,
            },
        });

        expect(wrapper.find('.delete-icon').exists()).toBe(true);
        expect(wrapper.find('.comment-edit-icon').exists()).toBe(true);
    });

    it('shows comment content if editing is false', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Comment, {
            localVue,
            propsData: {
                comment: testComment,
            },
        });

        expect(wrapper.html()).toContain('foo');
    });

    it('shows comment form if editing is true', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Comment, {
            localVue,
            propsData: {
                comment: testComment,
            },
        });

        wrapper.setData({editing: true});
        expect(wrapper.find('comment-form-stub').exists()).toBe(true);
    });

    it('likeCount updates when comment is liked', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(Comment, {
            localVue,
            propsData: {
                comment: testComment,
                loggedIn: true,
            },
        });

        wrapper.vm.likeComment();

        moxios.stubRequest('like_comment', {status: 200});

        moxios.wait(() => {
            expect(wrapper.vm.comment.likeCount).toBe(1);
            expect(wrapper.vm.comment.liked).toBe(true);
            done();
        });
    });

    it('likeCount updates correctly when comment is unliked', (done) => {
        const localVue = mockVue();
        let comment = Object.assign({}, testComment);
        comment.likeCount = 1; comment.liked = true;

        const wrapper = shallowMount(Comment, {
            localVue,
            propsData: {
                comment,
                loggedIn: true,
            },
        });

        wrapper.vm.likeComment();

        moxios.stubRequest('like_comment', {status: 200});

        moxios.wait(() => {
            expect(wrapper.vm.comment.likeCount).toBe(0);
            expect(wrapper.vm.comment.liked).toBe(false);
            done();
        });
    });

    it('api is not called when not logged user tries to like', () => {
        const localVue = createLocalVue();
        let mockAxios = {
            post: jest.fn(),
        };
        localVue.use({
            install(Vue, options) {
                Vue.prototype.$routing = {generate: (val) => val};
                Vue.prototype.$axios = {retry: mockAxios, single: mockAxios};
                Vue.prototype.$t = (val) => val;
            },
        });

        const wrapper = shallowMount(Comment, {
            localVue,
            propsData: {
                comment: testComment,
                loggedIn: false,
            },
        });

        wrapper.vm.likeComment();

        expect(mockAxios.post).not.toHaveBeenCalled();
    });
});
