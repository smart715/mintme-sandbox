import {shallowMount, createLocalVue} from '@vue/test-utils';
import Comments from '../../js/components/posts/Comments';
import {NotificationMixin} from '../../js/mixins';
import axios from 'axios';
import moxios from 'moxios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(NotificationMixin);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createCommentsProps(props = {}) {
    return {
        comments: [testComment],
        loggedIn: true,
        post: testPost,
        commentMinAmount: 1000,
        isOwner: true,
        ...props,
    };
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
};

describe('Comments', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(Comments, {
            localVue: localVue,
            propsData: createCommentsProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('shows no one commented yet if there are no comments', async () => {
        await wrapper.setProps({
            comments: [],
        });

        expect(wrapper.findAll('comment-stub').length).toBe(0);
        expect(wrapper.findComponent('.comments').html()).toContain('post.no_one_commented');
    });

    it('shows comments if comments is not empty', () => {
        expect(wrapper.findComponent('comment-stub').exists()).toBe(true);
    });

    it('update-comment emited', () => {
        wrapper.vm.updateComment(testComment);

        expect(wrapper.emitted('update-comment')[0][0]).toEqual(testComment);
    });

    it('confirm the delete of comment', async (done) => {
        await wrapper.setData({
            commentToDelete: testComment,
        });

        moxios.stubRequest('delete_comment', {
            status: 200,
            response: {
                data: {
                    message: 'OK',
                },
            },
        });

        wrapper.vm.deleteCommentConfirm();

        moxios.wait(() => {
            expect(wrapper.emitted('delete-comment')[0][0]).toBe(testComment);
            done();
        });
    });

    it('access denied when deleting comment', async (done) => {
        const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError').mockImplementation();
        await wrapper.setData({
            commentToDelete: testComment,
        });

        moxios.stubRequest('delete_comment', {
            status: 403,
            response: {
                message: 'error',
            },
        });

        wrapper.vm.deleteCommentConfirm();

        moxios.wait(() => {
            expect(notifyErrorSpy).toHaveBeenCalled();
            done();
        });
    });

    it('access denied when deleting comment and notifyError called once', async (done) => {
        const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError').mockImplementation();
        await wrapper.setData({
            commentToDelete: testComment,
        });

        moxios.stubRequest('delete_comment', {
            status: 403,
            response: {
                message: 'error',
            },
        });

        wrapper.vm.deleteCommentConfirm();

        moxios.wait(() => {
            expect(notifyErrorSpy).toHaveBeenCalled();
            done();
        });
    });

    it('deleting comment failed and notifyError called once', async (done) => {
        const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError').mockImplementation();
        await wrapper.setData({
            commentToDelete: testComment,
        });

        moxios.stubRequest('delete_comment', {
            status: 401,
            response: {
                message: 'error',
            },
        });

        wrapper.vm.deleteCommentConfirm();

        moxios.wait(() => {
            expect(notifyErrorSpy).toHaveBeenCalled();
            done();
        });
    });

    it('create comment failed', (done) => {
        const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError').mockImplementation();
        moxios.stubRequest('create_comment', {
            status: 401,
            response: {
                message: 'error',
            },
        });

        wrapper.vm.onCreateCommentError('foo');

        moxios.wait(() => {
            expect(notifyErrorSpy).toHaveBeenCalled();
            done();
        });
    });

    it('delete comment', () => {
        wrapper.vm.onDeleteComment(testComment);

        expect(wrapper.vm.commentToDelete).toEqual(testComment);
        expect(wrapper.vm.isDeleteConfirmVisible).toBe(true);
    });

    it('return in deleteCommentConfirm if isDeleting is true', async () => {
        await wrapper.setData({
            isDeleting: true,
        });

        wrapper.vm.deleteCommentConfirm();

        expect(wrapper.vm.isDeleting).toBe(true);
    });
});
