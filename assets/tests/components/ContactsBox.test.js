import {shallowMount, createLocalVue} from '@vue/test-utils';
import ContactsBox from '../../js/components/chat/ContactsBox.vue';
import Vuex from 'vuex';
import orderBy from 'lodash/orderBy';
import axios from 'axios';

const localVue = mockVue();

const propNickName = 'nicknameTest';

const threadsTest = [{
    id: 1,
    token: {
        name: 'tokenNameTest',
    },
    lastMessageTimestamp: '',
    hasUnreadMessages: '',
    metadata: [{
        isBlocked: false,
        participant: {
            id: 2,
            profile: {
                nickname: 'nameTest',
                image: {
                    avatar_middle: '',
                },
            },
        },
    }],
}];

/**
 * @return {Object}
 */
function contactList() {
    const contactList = {};
    Object.values(threadsParticipants()).forEach((participant) => {
        contactList[participant.threadId] = {
            id: participant.id,
            nickname: participant.profile.nickname,
            avatar: participant.profile.image.avatar_large,
            threadId: participant.threadId,
            tokenName: participant.tokenName,
            lastMessageTimestamp: participant.lastMessageTimestamp,
            lastMessage: participant.lastMessage,
            hasUnreadMessages: participant.hasUnreadMessages,
            isBlocked: participant.isBlocked,
            rankImg: null,
        };
    });
    return contactList;
};

/**
 * @return {Object}
 */
function threadsParticipants() {
    const participants = {};
    Object.values(threadsTest).forEach((thread) => {
        thread.metadata.forEach((metadata) => {
            if (metadata.participant.profile.nickname === propNickName) {
                return;
            }
            participants[metadata.participant.id] = {
                ...metadata.participant,
                threadId: thread.id,
                tokenName: thread.token.name,
                lastMessageTimestamp: thread.lastMessageTimestamp,
                hasUnreadMessages: thread.hasUnreadMessages,
                isBlocked: metadata.isBlocked,
                rankImg: null,
            };
        });
    });
    return participants;
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
                    getCurrentThreadId: () => 1,
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
        nickname: propNickName,
        threadIdProp: 1,
        threadsProp: threadsTest,
        topHolders: [],
        ...props,
    };
};

describe('ContactsBox', () => {
    let mutations;
    let store;
    let state;
    let wrapper;

    beforeEach(() => {
        mutations = {
            setContactName: jest.fn(),
            setTokenName: jest.fn(),
            setCurrentThreadId: jest.fn(),
        };

        state = {
            setContactName: 'contactNameTest',
            setTokenName: 'tokenNameTest',
            setCurrentThreadId: 1,
        };

        store = createSharedTestStore(mutations, state);

        wrapper = shallowMount(ContactsBox, {
            localVue: localVue,
            store: store,
            propsData: createSharedTestProps(),
        });
    });

    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: jest.fn().mockImplementation(() => ({
            addEventListener: jest.fn(),
        })),
    });

    it('Verify that "threadId" works correctly', () => {
        expect(wrapper.vm.threadId).toBe(1);
    });

    it('Verify that "participants" works correctly', async () => {
        await wrapper.setData({
            threads: threadsTest,
        });

        expect(wrapper.vm.participants).toEqual(threadsParticipants());
    });

    it('Verify that "contacts" works correctly', async () => {
        await wrapper.setData({
            threads: threadsTest,
        });

        expect(wrapper.vm.contacts).toEqual(contactList());
    });

    it('Verify that "contactsList" works correctly', async () => {
        await wrapper.setData({
            threads: threadsTest,
        });

        const orderByTest = orderBy(
            contactList(),
            ['isBlocked', 'lastMessageTimestamp'], ['asc', 'desc']
        );

        expect(wrapper.vm.contactsList).toEqual(orderByTest);
    });

    it('Verify that "changeChat" works correctly', async () => {
        wrapper.vm.changeChat();

        expect(wrapper.vm.threadId).toBe(1);
    });

    it('Verify that the "deleteChatModal" event is emitted correctly', async () => {
        const dataTest = 'jasm';

        wrapper.vm.deleteChatModal(dataTest);

        expect(wrapper.emitted('delete-chat-modal')).toBeTruthy();
        expect(wrapper.emitted('delete-chat-modal')[0]).toEqual([dataTest]);
    });
});
