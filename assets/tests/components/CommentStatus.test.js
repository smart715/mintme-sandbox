import {shallowMount, createLocalVue} from '@vue/test-utils';
import CommentStatus from '../../js/components/posts/CommentStatus';
import Vuex from 'vuex';
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
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
        },
    });

    return localVue;
}

/**
 * @return {Vuex.Store}
 */
function createSharedTestStore() {
    return new Vuex.Store({
        modules: {
            user: {
                namespaced: true,
                getters: {
                    getId: () => 1,
                },
            },
        },
    });
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createCommentStatusProps(props = {}) {
    return {
        isLoggedIn: true,
        comment: testComment,
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
    likeCount: 5,
    liked: true,
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

describe('CommentStatus', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(CommentStatus, {
            localVue: localVue,
            propsData: createCommentStatusProps(),
            store: createSharedTestStore(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('unlike the comment and reduce likes count', async () => {
        await wrapper.setData({
            isRequesting: false,
        });

        wrapper.vm.likeComment();

        expect(wrapper.vm.likes).toEqual(4);
        expect(wrapper.vm.isLiked).toBeFalsy();
    });

    it('like the comment and increase likes count', async () => {
        const comment = {...testComment, liked: false};

        await wrapper.setData({
            isRequesting: false,
        });

        await wrapper.setProps({
            comment: comment,
        });

        wrapper.vm.likeComment();

        expect(wrapper.vm.likes).toEqual(6);
        expect(wrapper.vm.isLiked).toBeTruthy();
    });

    it('not loggedin user likes comment and redirected to login page', async () => {
        global.window = Object.create(window);

        Object.defineProperty(window, 'location', {
            value: {
                href: 'url',
            },
        });

        wrapper.setData({
            isRequesting: false,
        });

        await wrapper.setProps({
            isLoggedIn: false,
        });

        wrapper.vm.likeComment();

        expect(window.location.href).toBe('login');
    });

    it('should watch comment', async () => {
        await wrapper.setProps({
            comment: {
                likeCount: 2,
                liked: false,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.likes).toEqual(2);
        expect(wrapper.vm.isLiked).toBe(false);
    });

    it('likecomment if isRequesting is true', async () => {
        await wrapper.setData({
            isRequesting: true,
        });

        wrapper.vm.likeComment();

        expect(wrapper.vm.likes).toEqual(5);
        expect(wrapper.vm.isLiked).toBe(true);
    });

    it('should call saveLike when axios request ok', async (done) => {
        wrapper.vm.saveLike = jest.fn();

        await wrapper.setData({
            isRequesting: false,
        });

        moxios.stubRequest('like_comment', {
            status: 200,
            response: {
                comment: {
                    likeCount: 2,
                    liked: true,
                },
            },
        });

        wrapper.vm.likeComment();

        moxios.wait(() => {
            expect(wrapper.vm.saveLike).toHaveBeenCalled();
            done();
        });
    });

    it('should call saveLike when catch error', async (done) => {
        wrapper.vm.saveLike = jest.fn();

        await wrapper.setData({
            isRequesting: false,
        });

        moxios.stubRequest('like_comment', {
            status: 500,
            response: {
                comment: {
                    likeCount: 2,
                    liked: true,
                },
            },
        });

        wrapper.vm.likeComment();

        moxios.wait(() => {
            expect(wrapper.vm.saveLike).toHaveBeenCalled();
            expect(wrapper.vm.isRequesting).toBe(false);
            done();
        });
    });
});
