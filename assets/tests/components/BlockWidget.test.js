import {shallowMount, createLocalVue} from '@vue/test-utils';
import BlockWidget from '../../js/components/chat/BlockWidget.vue';
import axios from 'axios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
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
        threadIdProp: 1,
        userIdProp: 1,
        isBlocked: false,
        ...props,
    };
}

describe('BlockWidget', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(BlockWidget, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    it('Verify that "btnBlockUser" returns the correct text', async () => {
        expect(wrapper.vm.btnBlockUser).toBe('chat.block_user');

        await wrapper.setProps({isBlocked: true});
        expect(wrapper.vm.btnBlockUser).toBe('chat.unblock_user');
    });

    it('Verify that "btnDeleteChat" returns the correct text', async () => {
        expect(wrapper.vm.btnDeleteChat).toBe('chat.delete_chat.label');
    });

    it('Verify that "deleteChatModal" works correctly', () => {
        wrapper.vm.deleteChatModal();

        const resultExpect = {
            isOpen: true,
            participantId: 1,
            threadId: 1,
        };

        expect(wrapper.emitted('delete-chat-modal')).toBeTruthy();
        expect(wrapper.emitted('delete-chat-modal')[0]).toEqual([resultExpect]);
    });
});
