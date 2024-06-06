<template>
    <div class="form-inline contacts-dropdown">
        <div class="form-group p-2">
            <select
                ref="selectContact"
                :value="threadId"
                class="form-control contact-nickname font-weight-bold"
                @change="changeContact"
            >
                <option
                    disabled
                    :key="0"
                    :value="0"
                    hidden
                >
                    {{ selectContact }}
                </option>
                <optgroup :label="selectContact">
                    <option
                    v-for="contact in notBlockedContacts"
                    :key="contact.threadId"
                    :value="contact.threadId"
                    class="text-center"
                    >
                        {{ contact.nickname | truncate(20) }}
                    </option>
                </optgroup>
                <optgroup :label="blockedContactsList">
                    <option
                    v-for="contact in blockedContacts"
                    :key="contact.threadId"
                    :value="contact.threadId"

                >
                    {{ contact.nickname | truncate(20) }}
                </option>
                </optgroup>
            </select>
        </div>
        <div class="form-group">
            <template v-for="contact in contacts">
                <block-widget
                    v-if="contact.threadId === threadId"
                    :key="contact.threadId"
                    :thread-id-prop="contact.threadId"
                    :user-id-prop="contact.id"
                    :is-blocked="contact.isBlocked"
                    @delete-chat-modal="deleteChatModal"
                ></block-widget>
            </template>
        </div>
    </div>
</template>

<script>
import BlockWidget from './BlockWidget';
import TruncateFilterMixin from '../../mixins/filters/truncate';

export default {
    name: 'ContactsDropdown',
    components: {BlockWidget},
    mixins: [TruncateFilterMixin],
    props: {
        nickname: String,
        threadId: Number,
        contacts: Array,
    },
    data() {
        return {
            selectedThreadId: null,
            blockedContactsList: this.$t('chat.blocked_contacts_list'),
            selectContact: this.$t('chat.chat_contacts.select_contact'),
        };
    },
    methods: {
        changeContact: function(e) {
            this.selectedThreadId = parseInt(e.target.value);
            this.$emit('change-contact', this.selectedThreadId);
        },
        deleteChatModal: function(open) {
            this.$emit('delete-chat-modal', open);
        },
    },
    computed: {
        blockedContacts() {
            return this.contacts.filter((contact)=>{
                if (contact.isBlocked) {
                    return contact;
                }
            });
        },
        notBlockedContacts() {
            return this.contacts.filter((contact)=>{
                if (!contact.isBlocked) {
                    return contact;
                }
            });
        },
    },
};
</script>
