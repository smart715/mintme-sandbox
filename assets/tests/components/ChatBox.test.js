import {shallowMount, createLocalVue} from '@vue/test-utils';
import ChatBox from '../../js/components/chat/ChatBox.vue';
import {GENERAL} from '../../js/utils/constants';
import moment from 'moment';
import Vuex from 'vuex';
import axios from 'axios';
import moxios from 'moxios';

const dateTest = new Date();

const messageTest = [{
    id: 1,
    body: 'message body',
    createdAt: dateTest,
    date: moment(dateTest).format(GENERAL.dayMonthFormat),
    sender: {
        profile: {
            nickname: 'userNameTest',
            image: {
                avatar_middle: '',
            },
        },
    },
}];

const messageTest2 = {
    id: 2,
    body: 'message body',
    createdAt: dateTest,
    date: moment(dateTest).format(GENERAL.dayMonthFormat),
    sender: {
        profile: {
            nickname: 'userNameTest',
            image: {
                avatar_middle: '',
            },
        },
    },
};

const threadsTest1 = [{
    id: 0,
    metadata: [
        {
            isBlocked: true,
            participant: {
                profile: {
                    nickname: 'myContactName',
                    isBlocked: true,
                },
            },
        },
    ],
}];

const threadsTest2 = [{
    id: 0,
    metadata: [
        {
            isBlocked: false,
            participant: {
                profile: {
                    nickname: 'myContactName',
                    isBlocked: false,
                },
            },
        },
    ],
}];

const threadsTest3 = [{
    id: 1,
    metadata: [
        {
            isBlocked: false,
            participant: {
                profile: {
                    nickname: 'nicknameTest',
                    isBlocked: false,
                },
            },
        },
    ],
}];

const messagesListTest = messageTest.map((message) => {
    return {
        id: message.id,
        nickname: message.sender.profile.nickname,
        body: message.body,
        avatar: message.sender.profile.image.avatar_middle,
        date: message.date,
        time: moment(message.createdAt).format(GENERAL.timeFormat),
    };
});

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
            Vue.prototype.$logger = {error: jest.fn()};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} mapGetters
 * @return {Wrapper<Vue>}
 */
function mockChatBox(props = {}, mapGetters = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {
            tradeBalance: {
                namespaced: true,
                getters: {
                    getQuoteFullBalance: () => 0,
                    ...mapGetters,
                },
            },
            chat: {
                namespaced: true,
                getters: {
                    getContactName: () => 'myContactName',
                    getTokenName: () => 'tokenName',
                    getUserTokenName: () => '',
                    getDMMinAmount: () => 0,
                    getCurrentThreadId: () => 0,
                    getRankImg: () => null,
                    ...mapGetters,
                },
            },
        },
    });

    ChatBox.methods.updateMessagesInterval = jest.fn();
    ChatBox.methods.sendNextMessage = jest.fn();

    const wrapper = shallowMount(ChatBox, {
        store,
        localVue: localVue,
        propsData: {
            chatReady: false,
            threads: [{metadata: {}}],
            nickname: 'nicknameTest',
            currentLang: 'en',
            ...props,
        },
    });

    return wrapper;
}

describe('ChatBox', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mockChatBox();
    });

    it('Verify that "hasMessages" works correctly', async () => {
        expect(wrapper.vm.hasMessages).toBe(false);

        await wrapper.setData({
            messages: messageTest,
        });

        expect(wrapper.vm.hasMessages).toBe(true);
    });

    it('Verify that "messagesLoaded" works correctly', async () => {
        expect(wrapper.vm.messagesLoaded).toBe(false);

        await wrapper.setData({
            messages: messageTest,
        });

        expect(wrapper.vm.hasMessages).toBe(true);
    });

    it('Verify that "showInput" works correctly', async () => {
        expect(wrapper.vm.showInput).toBe(false);

        await wrapper.setProps({
            chatReady: true,
        });

        await wrapper.setData({
            messages: messageTest,
        });

        expect(wrapper.vm.showInput).toBe(true);
    });

    it('Verify that "contactName" works correctly', async () => {
        expect(wrapper.vm.contactName).toBe('-');

        await wrapper.setData({
            messages: messageTest,
        });

        expect(wrapper.vm.contactName).toBe('myContactName');
    });

    it('Verify that "messagesUrl" works correctly', () => {
        expect(wrapper.vm.messagesUrl).toBe('get_messages');
    });

    it('Verify that "newMessagesUrl" works correctly', () => {
        expect(wrapper.vm.newMessagesUrl).toBe('get_new_messages');
    });

    it('Verify that "messagesList" works correctly', async () => {
        expect(wrapper.vm.messagesList).toEqual([]);

        await wrapper.setData({
            messages: messageTest,
        });

        expect(wrapper.vm.messagesList).toEqual(messagesListTest);
    });

    it('Verify that "lastMessageId" works correctly', async () => {
        expect(wrapper.vm.lastMessageId).toBe(0);

        await wrapper.setData({
            messages: messageTest,
        });

        expect(wrapper.vm.lastMessageId).toBe(1);
    });

    it('Verify that "translationContext" works correctly', () => {
        const dataTest = {
            amount: 0,
            currency: 'tokenName',
        };

        expect(wrapper.vm.translationContext).toEqual(dataTest);
    });

    it('Verify that "isSendDisabled" works correctly without data in the "threads" prop', () => {
        expect(wrapper.vm.isSendDisabled).toBe(false);
    });

    it('should correctly computes messagesList when messages is empty', async () => {
        await wrapper.setData({messages: []});

        expect(wrapper.vm.messagesList).toEqual([]);
    });

    it('correctly computes lastMessageId when messagesList is empty', async () => {
        await wrapper.setData({messages: []});

        expect(wrapper.vm.lastMessageId).toBe(0);
    });

    describe('participant', () => {
        it('returns an empty object if threads are empty', async () => {
            await wrapper.setProps({threads: []});

            expect(wrapper.vm.participant).toEqual({});
        });

        it('returns correct value when threads are filled', async () => {
            await wrapper.setProps({threads: threadsTest1});
            await wrapper.setData({messages: messageTest});

            expect(wrapper.vm.participant).toEqual({
                isBlocked: true,
                profile: {
                    nickname: 'myContactName',
                    isBlocked: true,
                },
            });
        });
    });

    describe('iconBlockUser', () => {
        it('return correct icon if participant is Blocked', async () => {
            await wrapper.setProps({threads: threadsTest1});
            await wrapper.setData({messages: messageTest});

            expect(wrapper.vm.iconBlockUser).toEqual({prefix: 'far', iconName: 'check-circle'});
        });

        it('return correct icon if participant is not Blocked', async () => {
            await wrapper.setProps({threads: threadsTest2});
            await wrapper.setData({messages: messageTest});

            expect(wrapper.vm.iconBlockUser).toEqual({prefix: 'fa', iconName: 'ban'});
        });
    });

    describe('addMessageToQueue', () => {
        it('call notifyInfo if tokenName !== userTokenName and quoteFullBalance < dMMinAmount', async () => {
            wrapper = mockChatBox({}, {
                getDMMinAmount: () => 100,
                getQuoteFullBalance: () => 0,
            });
            wrapper.vm.notifyInfo = jest.fn();

            await wrapper.setData({messageBody: 'Test message'});

            await wrapper.vm.addMessageToQueue();

            expect(wrapper.vm.notifyInfo).toHaveBeenCalled();
        });

        it('add messagebody to messagesQueue if quoteFullBalance > dMMinAmount', async () => {
            wrapper = mockChatBox({}, {
                getDMMinAmount: () => 0,
                getQuoteFullBalance: () => 100,
            });

            await wrapper.setData({messageBody: 'Test message', isQueueRunning: true});

            await wrapper.vm.addMessageToQueue();

            expect(wrapper.vm.messagesQueue[0]).toEqual('Test message');
        });
    });

    describe('runQueue', () => {
        it('call sendNextMessage if isQueueRunning = false', async () => {
            wrapper = mockChatBox();
            const sendNextMessage = jest.spyOn(wrapper.vm, 'sendNextMessage');

            await wrapper.setData({isQueueRunning: false});

            await wrapper.vm.runQueue();

            expect(sendNextMessage).toHaveBeenCalled();
        });
    });

    describe('sendMessage', () => {
        beforeEach(() => {
            moxios.install();
        });


        afterEach(() => {
            moxios.uninstall();
        });

        it('calls notifyError if response == blocked', async (done) => {
            wrapper = mockChatBox();
            delete window.location;
            window.location = {
                reload: jest.fn(),
            };
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('send_dm_message', {
                status: 200,
                response: {
                    status: 'blocked',
                },
            });

            await wrapper.vm.sendMessage();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                done();
            });
        });

        it('calls sendNextMessage if response !== blocked', async (done) => {
            wrapper = mockChatBox();
            const sendNextMessage = jest.spyOn(wrapper.vm, 'sendNextMessage');

            moxios.stubRequest('send_dm_message', {
                status: 200,
                response: {
                    status: 'not-blocked',
                },
            });

            await wrapper.vm.sendMessage();

            moxios.wait(() => {
                expect(sendNextMessage).toHaveBeenCalled();
                done();
            });
        });

        it('calls notifyError if request throws error with HTTP_ACCESS_DENIED status', async (done) => {
            wrapper = mockChatBox();
            wrapper.vm.notifyError = jest.fn();

            moxios.stubRequest('send_dm_message', {
                status: 403,
                response: {
                    message: 'Error message',
                },
            });

            await wrapper.vm.sendMessage();

            moxios.wait(() => {
                expect(wrapper.vm.notifyError).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('getMessages', () => {
        beforeEach(() => {
            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('sets messages to null and prompts execution if threadId == 0', async (done) => {
            await wrapper.vm.getMessages();

            moxios.wait(() => {
                expect(wrapper.vm.messages).toEqual(null);
                expect(wrapper.vm.loading).toEqual(false);
                done();
            });
        });

        it('calls updateMessagesInterval if request succeed', async (done) => {
            wrapper = mockChatBox(
                {
                    threads: threadsTest3,
                },
                {
                    getCurrentThreadId: () => 1,
                    getContactName: () => 'nicknameTest',
                });
            const updateMessagesInterval = jest.spyOn(wrapper.vm, 'updateMessagesInterval');

            moxios.stubRequest('get_messages', {
                status: 200,
            });

            await wrapper.vm.getMessages();

            moxios.wait(() => {
                expect(updateMessagesInterval).toHaveBeenCalled();
                done();
            });
        });

        it('calls logger.error if request fails', async (done) => {
            wrapper = mockChatBox({
                threads: threadsTest3,
            },
            {
                getCurrentThreadId: () => 1,
            });

            moxios.stubRequest('get_messages', {
                status: 400,
            });

            await wrapper.vm.getMessages();

            moxios.wait(() => {
                expect(wrapper.vm.$logger.error).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('updateMessages', () => {
        beforeEach(() => {
            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('prompt execution if messages are not Loaded', async (done) => {
            await wrapper.vm.updateMessages();

            moxios.wait(() => {
                expect(moxios.requests.count()).toBe(0);
                done();
            });
        });

        it('calls logger.error if request fails', async (done) => {
            await wrapper.setData({messages: messageTest});

            moxios.stubRequest('get_new_messages', {
                status: 400,
            });

            await wrapper.vm.updateMessages();

            moxios.wait(() => {
                expect(wrapper.vm.$logger.error).toHaveBeenCalled();
                done();
            });
        });

        it('adds response data to messages if request succeed', async (done) => {
            await wrapper.setData({messages: messageTest});

            moxios.stubRequest('get_new_messages', {
                status: 200,
                response: messageTest2,
            });

            await wrapper.vm.updateMessages();

            moxios.wait(() => {
                expect(wrapper.vm.messages).toBe(messageTest);
                done();
            });
        });
    });

    describe('toggleBlockUser', () => {
        beforeEach(() => {
            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('calls notifySuccess and replaces window location on successful request', async () => {
            const responseData = {
                message: 'User blocked successfully',
            };

            moxios.stubRequest('block_user', {
                status: 200,
                response: responseData,
            });

            wrapper.vm.notifySuccess = jest.fn();

            const mockReplace = jest.fn();
            Object.defineProperty(window.location, 'replace', {
                value: mockReplace,
            });

            await wrapper.vm.toggleBlockUser();

            expect(wrapper.vm.notifySuccess).toHaveBeenCalledWith(responseData.message);
            expect(mockReplace).toHaveBeenCalledWith(wrapper.vm.$routing.generate('chat'));
        });

        it('calls notifyError on failing request', async () => {
            moxios.stubRequest('block_user', {
                status: 400,
            });

            wrapper.vm.notifyError = jest.fn();

            await wrapper.vm.toggleBlockUser();

            expect(wrapper.vm.notifyError).toHaveBeenCalled();
        });
    });

    describe('handleKeypress', () => {
        it('adds message to queue and prevents default on Enter press', () => {
            wrapper = mockChatBox();
            const addMessageToQueue = jest.spyOn(wrapper.vm, 'addMessageToQueue');

            const mockEvent = {
                key: 'Enter',
                ctrlKey: false,
                shiftKey: false,
                target: {
                    selectionStart: 0,
                    selectionEnd: 0,
                    value: '',
                },
                preventDefault: jest.fn(),
            };

            wrapper.vm.handleKeypress(mockEvent);

            expect(addMessageToQueue).toHaveBeenCalled();

            expect(mockEvent.preventDefault).toHaveBeenCalled();
        });

        it('appends newline character on Ctrl+Enter press', () => {
            const mockEvent = {
                key: 'Enter',
                ctrlKey: true,
                shiftKey: false,
                target: {
                    selectionStart: 5,
                    selectionEnd: 5,
                    value: 'Hello world',
                },
            };

            wrapper.vm.handleKeypress(mockEvent);

            expect(wrapper.vm.messageBody).toBe('Hello\n world');
        });
    });

    describe('handleKeypress', () => {
        it('emits "delete-chat-modal" event with correct data', () => {
            const emitSpy = jest.spyOn(wrapper.vm, '$emit');

            wrapper.vm.deleteChatModal();

            expect(emitSpy).toHaveBeenCalled();
        });
    });
});
