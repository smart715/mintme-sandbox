import {shallowMount, createLocalVue} from '@vue/test-utils';
import SinglePost from '../../js/components/posts/SinglePost';
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
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSinglePostProps(props = {}) {
    return {
        subunit: 4,
        tokenName: 'MySuperToken',
        tokenPage: false,
        showEdit: true,
        loggedIn: true,
        isOwner: true,
        ...props,
        token: {name: 'TOK'},
    };
}

/**
 * @param {Object} mutations
 * @return {Vuex.Store}
 */
function createSinglePostStore(mutations) {
    return new Vuex.Store({
        modules: {
            posts: {
                mutations,
                namespaced: true,
                getters: {
                    getSinglePost: () => {},
                },
            },
        },
    });
}

const post = {
    id: 1,
    title: 'My post',
    content: 'My post content',
};

describe('SinglePost', () => {
    let wrapper;
    let mutations;
    let store;

    beforeEach(() => {
        mutations = {
            setSinglePost: jest.fn(),
            updatePost: jest.fn(),
            deletePost: jest.fn(),
        };

        store = createSinglePostStore(mutations);

        wrapper = shallowMount(SinglePost, {
            localVue: localVue,
            sync: false,
            store: store,
            propsData: createSinglePostProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should update post', () => {
        wrapper.vm.onEditPostSuccess(post);

        expect(mutations.updatePost).toHaveBeenCalledWith({}, post);
    });

    it('should delete post', () => {
        wrapper.vm.onDeletePostSuccess();

        expect(wrapper.emitted()['post-deleted'][0]).toBeTruthy();
    });
});
