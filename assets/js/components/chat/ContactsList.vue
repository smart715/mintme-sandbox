<template>
    <div class="table-responsive fixed-head-table h-100 mb-0">
        <b-table
            v-if="hasContacts"
            class="w-100"
            :items="contacts"
            :fields="fields">
            <template v-slot:cell(trader)="row">
                <div
                    @click="changeContact(row.item.threadId)"
                    class="d-flex c-pointer flex-row flex-nowrap justify-content-between align-items-center w-100 py-2 text-white">
                    <img
                        :src="row.item.avatar"
                        class="chat-avatar rounded-circle d-block"
                        alt="avatar">
                    <span class="d-inline-block truncate-name col">
                        <span>
                            {{ row.item.nickname }}
                        </span>
                    </span>
                </div>
            </template>
        </b-table>
        <div v-else>
            <p class="text-center p-5">{{ $t('chat.chat_contacts.no_contacts') }}</p>
        </div>
    </div>
</template>

<script>

export default {
    name: 'ContactsList',
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
                    label: this.$t('chat.chat_contacts.contacts'),
                },
            ],
        };
    },
    computed: {
        hasContacts: function() {
            return this.contacts.length > 0;
        },
    },
    methods: {
        changeContact: function(threadId) {
            this.$emit('change-contact', threadId);
        },
    },
};
</script>
