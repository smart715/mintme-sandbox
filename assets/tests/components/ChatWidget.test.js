import {shallowMount, createLocalVue} from '@vue/test-utils';
import ChatWidget from '../../js/components/chat/ChatWidget.vue';
import axios from 'axios';
import moxios from 'moxios';
import Vuex from 'vuex';

const localVue = mockVue();

const marketTest = {
    base: {
        name: 'BTC',
        symbol: 'BTC',
        subunit: 8,
        identifier: 'BTC',
    },
    quote: {
        name: 'Webchain',
        symbol: 'WEB',
        subunit: 4,
        identifier: 'WEB',
    },
};

/**
 * @param {Object} mutations
 * @param {Object} state
 * @return {Vuex.Store}
 */
function createSharedTestStore(mutations, state) {
    return new Vuex.Store({
        modules: {
            chat: {
                mutations,
                state,
                namespaced: true,
                getters: {
                    getCurrentThreadId: () => 2,
                },
            },
        },
    });
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        nickname: 'nickNameTest',
        threadIdProp: 1,
        threads: [],
        dMMinAmount: 2,
        userTokenName: 'userTokenName',
        tokenPrecision: 8,
        websocketUrl: '',
        userHash: '',
        currentLang: '',
        ...props,
    };
};

describe('ChatWidget', () => {
    let wrapper;
    let mutations;
    let store;
    let state;

    beforeEach(() => {
        mutations = {
            setDMMinAmount: jest.fn(),
            setUserTokenName: jest.fn(),
        };

        state = {
            setDMMinAmount: 100,
            setUserTokenName: 'userTokenName',
        };

        store = createSharedTestStore(mutations, state);

        wrapper = shallowMount(ChatWidget, {
            localVue: localVue,
            store: store,
            propsData: createSharedTestProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Verify that "deleteChatModal" works correctly', async () => {
        const dataTest = {
            threadId: 1,
            participantId: 1,
            isOpen: true,
        };

        await wrapper.setData({
            useDeleteChatModal: false,
            deleteChatData: {
                threadId: null,
                participantId: null,
            },
        });

        wrapper.vm.deleteChatModal(dataTest);

        expect(wrapper.vm.deleteChatData.threadId).toBe(dataTest.threadId);
        expect(wrapper.vm.deleteChatData.participantId).toBe(dataTest.participantId);
        expect(wrapper.vm.useDeleteChatModal).toBe(dataTest.isOpen);
    });

    it('Verify that "closeDeleteChatModal" works correctly', async () => {
        await wrapper.setData({
            useDeleteChatModal: true,
        });

        wrapper.vm.closeDeleteChatModal();

        expect(wrapper.vm.useDeleteChatModal).toBe(false);
    });

    it('Verify that "getContacts" works correctly', async (done) => {
        moxios.stubRequest('get_contacts', {
            status: 200,
            response: {
                contactList: ['contactsList'],
            },
        });

        await wrapper.vm.getContacts();

        moxios.wait(() => {
            expect(wrapper.vm.threads).toEqual(['contactsList']);
            done();
        });
    });

    it('Verify that "updateMarket" returns the correct text', async (done) => {
        moxios.stubRequest('get_thread_market', {
            status: 200,
            response: {
                marketTest,
            },
        });

        await wrapper.vm.updateMarket();

        expect(wrapper.vm.market).toBe(null);

        moxios.wait(() => {
            expect(wrapper.vm.market).toEqual({marketTest});
            done();
        });
    });

    it('should hide delete chat modal on close event', () => {
        const wrapper = shallowMount(ChatWidget, {
            localVue: localVue,
            store: store,
            propsData: createSharedTestProps(),
        });

        wrapper.vm.deleteChatModal({isOpen: true});

        expect(wrapper.vm.useDeleteChatModal).toBe(true);

        wrapper.vm.closeDeleteChatModal();

        expect(wrapper.vm.useDeleteChatModal).toBe(false);
    });

    it('should show error notification when deleteChat method is called and error occurs', async (done) => {
        const expectResult = {
            participantId: null,
            threadId: null,
        };

        moxios.stubRequest('delete_chat', {
            status: 403,
            response: {
                data: {
                    message: 'Access denied',
                },
            },
        });

        wrapper.vm.notifyError = jest.fn();

        await wrapper.vm.deleteChat();

        moxios.wait(() => {
            expect(wrapper.vm.useDeleteChatModal).toBe(false);
            expect(wrapper.vm.deleteChatData).toEqual(expectResult);
            expect(wrapper.vm.notifyError).toHaveBeenCalled();
            done();
        });
    });

    it('should display error notification when HTTP_ACCESS_DENIED error occurs', async (done) => {
        wrapper.vm.notifyError = jest.fn();
        wrapper.vm.deleteChatData.threadId = 1;
        wrapper.vm.deleteChatData.participantId = 2;

        moxios.stubRequest('delete_chat', {
            status: 403,
            response: {
                message: 'Access denied',
            },
        });

        await wrapper.vm.deleteChat();

        moxios.wait(() => {
            expect(wrapper.vm.notifyError).toHaveBeenCalledWith('Access denied');
            done();
        });
    });
});
