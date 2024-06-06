import {shallowMount, createLocalVue} from '@vue/test-utils';
import ContactsList from '../../js/components/chat/ContactsList.vue';

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
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockContactsList(props = {}) {
    return shallowMount(ContactsList, {
        localVue: mockVue(),
        stubs: ['b-table'],
        propsData: {
            nickname: 'nicknameTest',
            threadId: 1,
            contacts: [],
            ...props,
        },
    });
}

describe('ContactsList', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mockContactsList();
    });

    it('Verify that "hasContacts" works correctly', async () => {
        const wrapper = mockContactsList();

        expect(wrapper.vm.hasContacts).toBe(false);

        await wrapper.setProps({
            contacts: contact,
        });

        expect(wrapper.vm.hasContacts).toBe(true);
    });

    it('Verify that "disabledContact" works correctly', async () => {
        const wrapper = mockContactsList();

        await wrapper.setProps({
            contacts: contact,
        });

        const itemTest = {
            item: contact[0],
        };

        expect(wrapper.vm.disabledContact(itemTest)).toBe('');

        itemTest.item.isBlocked = true;
        expect(wrapper.vm.disabledContact(itemTest)).toEqual({'opacity': '0.4'});
    });

    it('Verify `b-table` is displayed or not', async () => {
        const wrapper = mockContactsList();

        expect(wrapper.findComponent({ref: 'tableContact'}).exists()).toBe(false);

        await wrapper.setProps({
            contacts: contact,
        });

        expect(wrapper.findComponent({ref: 'tableContact'}).exists()).toBe(true);
    });

    it('displays a message if there are no contacts', async () => {
        await wrapper.setProps({contacts: []});

        expect(wrapper.vm.hasContacts).toBe(false);
    });

    it('displays contacts if there are contacts', async () => {
        await wrapper.setProps({contacts: ['TEST']});

        expect(wrapper.vm.hasContacts).toBe(true);
    });

    it('Verify that the "changeContact" event is emitted correctly', async () => {
        wrapper.vm.changeContact(wrapper.vm.threadId);

        expect(wrapper.emitted('change-contact')).toBeTruthy();
        expect(wrapper.emitted('change-contact')[0]).toStrictEqual([wrapper.vm.threadId]);
    });

    it('Verify that the "deleteChatModal" event is emitted correctly', async () => {
        const dataTest = 'jasm';

        wrapper.vm.deleteChatModal(dataTest);

        expect(wrapper.emitted('delete-chat-modal')).toBeTruthy();
        expect(wrapper.emitted('delete-chat-modal')[0]).toEqual([dataTest]);
    });

    it('Verify that the "profileUrl" method generates the route correctly', async () => {
        expect(wrapper.vm.profileUrl(wrapper.vm.nickname)).toBe('profile-view');
    });
});
