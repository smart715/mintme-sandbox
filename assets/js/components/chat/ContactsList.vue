<template>
    <div class="table-responsive fixed-head-table h-100 mb-0">
        <b-table
            v-if="hasContacts"
            ref="tableContact"
            class="w-100"
            thead-class="d-none"
            :items="contacts"
            :fields="fields"
        >
            <template v-slot:cell(trader)="row">
                <div
                    class="d-flex c-pointer flex-row flex-nowrap justify-content-start
                           align-items-center w-100 py-2 px-3 font-weight-bold"
                    :style="disabledContact(row)"
                    @click="changeContact(row.item.threadId)"
                >
                    <div class="position-relative">
                        <a :href="profileUrl(row.item.nickname)">
                            <img
                                :src="row.item.avatar"
                                class="chat-avatar rounded-circle d-block"
                            />
                        </a>
                    </div>
                    <span class="col">
                        <span
                            class="d-block contact-nickname text-truncate"
                            :class="{ 'contact-nickname-selected': threadId === row.item.threadId }"
                        >
                            <img
                                v-if="row.item.rankImg"
                                :src="row.item.rankImg"
                                class="mr-1"
                                alt="medal"
                            />
                            {{ row.item.nickname }}
                        </span>
                        <span class="d-block contact-last-msg text-truncate">
                            {{ row.item.lastMessage }}
                        </span>
                    </span>
                </div>
            </template>
            <template v-slot:cell(menu)="row">
                <div class="d-flex justify-content-end">
                    <div
                        v-if="row.item.hasUnreadMessages"
                        class="contact-badge mr-3 p-3"
                    />
                    <div v-else>
                        <block-widget
                            :user-id-prop="row.item.id"
                            :thread-id-prop="row.item.threadId"
                            :is-blocked="row.item.isBlocked"
                            @delete-chat-modal="deleteChatModal"
                        />
                    </div>
                </div>
            </template>
        </b-table>
        <div v-else>
            <p class="text-center p-5 message-subtitle">{{ $t('chat.chat_contacts.no_contacts') }}</p>
        </div>
    </div>
</template>

<script>
import {BTable} from 'bootstrap-vue';
import BlockWidget from './BlockWidget';

export default {
    name: 'ContactsList',
    components: {
        BTable,
        BlockWidget,
    },
    props: {
        nickname: String,
        threadId: Number,
        contacts: Array,
    },
    data() {
        return {
            fields: [
                {
                    key: 'trader',
                    label: 'contacts:',
                    class: 'contact p-0',
                },
                {
                    key: 'menu',
                    label: 'menu',
                    tdClass: 'text-right chat-td',
                },
            ],
        };
    },
    computed: {
        hasContacts: function() {
            return 0 < this.contacts.length;
        },
        disabledContact() {
            return (row)=> row.item.isBlocked ? {'opacity': '0.4'} : '';
        },
    },
    methods: {
        changeContact: function(threadId) {
            this.$emit('change-contact', threadId);
        },
        deleteChatModal: function(data) {
            this.$emit('delete-chat-modal', data);
        },
        profileUrl(userProfileName) {
            return this.$routing.generate('profile-view', {nickname: userProfileName});
        },
    },
};
</script>
