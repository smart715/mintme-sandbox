<template>
    <div class="form-group">
        <select
            :value="threadId"
            class="form-control"
            @change="changeContact"
        >
            <option
                disabled
                :key="0"
                :value="0"
            >
               {{ $t('chat.chat_contacts.select_contact') }}
            </option>
            <option
                v-for="contact in contacts"
                :key="contact.threadId"
                :value="contact.threadId"
            >
                {{ contact.nickname }}
            </option>
        </select>
    </div>
</template>

<script>

export default {
    name: 'ContactsDropdown',
    props: {
        nickname: String,
        threadId: Number,
        contacts: Array,
    },
    methods: {
        changeContact: function(e) {
            this.$emit('change-contact', parseInt(e.target.value));
        },
    },
};
</script>
