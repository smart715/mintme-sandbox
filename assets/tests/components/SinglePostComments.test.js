import {shallowMount, createLocalVue} from '@vue/test-utils';
import SinglePostComments from '../../js/components/posts/SinglePostComments';
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
function createSinglePostCommentsProps(props = {}) {
    return {
        loggedIn: true,
        isLoading: false,
        commentMinAmount: 1,
        isOwner: true,
        ...props,
    };
};

/**
 * @param {Object} mutations
 * @return {Vuex.Store}
 */
function createSinglePostCommentsPropsStore(mutations) {
    return new Vuex.Store({
        modules: {
            posts: {
                mutations,
                namespaced: true,
                getters: {
                    getSinglePost: () => {},
                    getComments: () => {},
                },
            },
            user: {
                namespaced: true,
                getters: {
                    getOwnDeployedTokens: () => [],
                },
            },
        },
    });
};

const comment = {
    id: 1,
    content: 'My comment',
    user: {
        id: 1,
        name: 'Edgar Allan Poe',
    },
};

describe('SinglePost', () => {
    let wrapper;
    let mutations;
    let store;

    beforeEach(() => {
        mutations = {
            addComment: jest.fn(),
            editComment: jest.fn(),
            removeCommentById: jest.fn(),
        };

        store = createSinglePostCommentsPropsStore(mutations);

        wrapper = shallowMount(SinglePostComments, {
            localVue: localVue,
            sync: false,
            store: store,
            propsData: createSinglePostCommentsProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should delete comment', () => {
        wrapper.vm.onCommentDelete(comment);

        expect(mutations.removeCommentById).toHaveBeenCalledWith({}, comment.id);
    });

    it('should add comment', () => {
        wrapper.vm.onCommentAdd(comment);

        expect(mutations.addComment).toHaveBeenCalledWith({}, comment);
    });

    it('should edit comment', () => {
        wrapper.vm.onCommentUpdate(comment);

        expect(mutations.editComment).toHaveBeenCalledWith({}, comment);
    });
});
