import {shallowMount, createLocalVue} from '@vue/test-utils';
import Comment from '../../js/components/posts/Comment';
import axios from 'axios';
import moxios from 'moxios';

const localVue = mockVue();

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
            Vue.prototype.$toasted = {show: () => false};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createCommentProps(props = {}) {
    return {
        commentProp: testComment,
        index: 0,
        loggedIn: true,
        commentMinAmount: 0,
        isOwner: true,
        topHolders: [],
        ...props,
    };
};

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

describe('Comment', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(Comment, {
            localVue: localVue,
            propsData: createCommentProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('does not show delete and edit icons if editable is false', () => {
        expect(wrapper.findComponent('.delete-icon').exists()).toBe(false);
        expect(wrapper.findComponent('.comment-edit-icon').exists()).toBe(false);
    });

    it('shows delete and edit icons if editable is true', async () => {
        const comment = Object.assign({}, testComment);

        comment.editable = true;
        comment.deletable = true;

        await wrapper.setProps({
            commentProp: comment,
        });

        await wrapper.setData({
            comment: comment,
        });

        expect(wrapper.findComponent('.delete-icon').exists()).toBe(true);
        expect(wrapper.findComponent('.comment-edit-icon').exists()).toBe(true);
    });

    it('shows comment content if editing is false', () => {
        expect(wrapper.html()).toContain('foo');
    });

    it('shows comment form if editing is true', async () => {
        await wrapper.setData({
            editing: true,
            comment: testComment,
        });

        expect(wrapper.findComponent('comment-form-stub').exists()).toBe(true);
    });

    it('likeCount updates when comment is liked', async (done) => {
        await wrapper.setData({
            comment: testComment,
        });

        moxios.stubRequest('like_comment', {
            status: 200,
        });

        wrapper.vm.likeComment();


        moxios.wait(() => {
            expect(wrapper.emitted('update-comment')[0][0].likeCount).toBe(1);
            expect(wrapper.emitted('update-comment')[0][0].liked).toBe(true);
            done();
        });
    });

    it('likeCount updates correctly when comment is unliked', async (done) => {
        const comment = Object.assign({}, testComment);

        comment.likeCount = 1;
        comment.liked = true;

        await wrapper.setData({
            comment: comment,
        });

        moxios.stubRequest('like_comment', {
            status: 200,
        });

        wrapper.vm.likeComment();

        moxios.wait(() => {
            expect(wrapper.emitted('update-comment')[0][0].likeCount).toBe(0);
            expect(wrapper.emitted('update-comment')[0][0].liked).toBe(false);
            done();
        });
    });

    it('likeComment should call notifyError if error.response.data.message', async (done) => {
        const comment = Object.assign({}, testComment);

        comment.likeCount = 1;
        comment.liked = true;

        const notfiyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

        await wrapper.setData({
            comment: comment,
        });

        moxios.stubRequest('like_comment', {
            status: 403,
            response: {message: 'error'},
        });

        wrapper.vm.likeComment();

        moxios.wait(() => {
            expect(notfiyErrorSpy).toHaveBeenCalled();
            done();
        });
    });

    it('likeComment should call notifyError comment.delete_failed if error.response.status !== 403', async (done) => {
        const comment = Object.assign({}, testComment);
        comment.likeCount = 1;
        comment.liked = true;

        const notfiyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

        await wrapper.setData({
            comment: comment,
        });

        moxios.stubRequest('like_comment', {
            status: 500,
            response: {message: 'error'},
        });

        wrapper.vm.likeComment();

        moxios.wait(() => {
            expect(notfiyErrorSpy).toHaveBeenCalled();
            done();
        });
    });

    it('api is not called when not logged user tries to like', async () => {
        const assignMock = jest.fn();
        delete window.location;
        window.location = {assign: assignMock};

        const mockAxios = {
            post: jest.fn(),
            get: jest.fn(),
        };

        delete window.location;
        window.location = {
            reload: jest.fn(),
        };

        await wrapper.setProps({
            loggedIn: false,
        });

        wrapper.vm.likeComment();

        assignMock.mockClear();
        expect(mockAxios.post).not.toHaveBeenCalled();
        expect(location.href).toBe('login');
    });

    it('likeComment return if liking is true', async () => {
        await wrapper.setData({
            liking: true,
        });

        wrapper.vm.likeComment();

        expect(wrapper.vm.liking).toBe(true);
    });

    it('onEditCommentSuccess updates comment', () => {
        wrapper.vm.onEditCommentSuccess(testComment);

        expect(wrapper.vm.comment).toEqual(testComment);
        expect(wrapper.emitted('update-comment')[0]).toBeTruthy();
        expect(wrapper.vm.editing).toBe(false);
    });

    it('deleteComment emits delete-comment event', () => {
        wrapper.vm.deleteComment();

        expect(wrapper.emitted()['delete-comment'][0]).toBeTruthy();
    });
});
