import {shallowMount, createLocalVue} from '@vue/test-utils';
import ContactsDropdown from '../../js/components/chat/ContactsDropdown.vue';


const contact = [{
    id: 2,
    nickname: 'nameTest',
    avatar: '',
    threadId: 1,
    tokenName: 'tokenNameTest',
    lastMessageTimestamp: '',
    hasUnreadMessages: '',
    isBlocked: false,
}];

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockContactsDropdown(props = {}) {
    return shallowMount(ContactsDropdown, {
        localVue: mockVue(),
        propsData: {
            nickname: 'nicknameTest',
            threadId: 1,
            contacts: [],
            ...props,
        },
    });
}

describe('ContactsDropdown', () => {
    it('Verify that "changeContact" works correctly', async () => {
        const wrapper = mockContactsDropdown();
        const select = wrapper.findComponent({ref: 'selectContact'});

        await select.trigger('change');

        expect(wrapper.emitted()).toHaveProperty('change-contact');
    });

    it('Verify that "notBlockedContacts" works correctly', async () => {
        const wrapper = mockContactsDropdown();

        await wrapper.setProps({
            contacts: contact,
        });

        expect(wrapper.vm.notBlockedContacts).toEqual(contact);
    });

    it('Verify that "blockedContacts" works correctly', async () => {
        const wrapper = mockContactsDropdown();

        await wrapper.setProps({
            contacts: contact,
        });

        expect(wrapper.vm.blockedContacts).toEqual([]);
    });
});
