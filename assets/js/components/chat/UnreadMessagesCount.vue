<template>
    <div>
        <p v-if="loaded" class="text-center p-5">{{ $t('chat.chat_box.unread_messages_text', translationContext) }}</p>
        <div v-else class="p-5 text-center">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
    </div>
</template>

<script>
import {LoggerMixin} from '../../mixins';

export default {
    name: 'UnreadMessagesCount',
    data() {
        return {
            count: null,
        };
    },
    mixin: {
        LoggerMixin,
    },
    computed: {
        loaded: function() {
            return this.count !== null;
        },
        translationContext: function() {
            return {
                count: this.count,
            };
        },
    },
    methods: {
        loadCount: function() {
            this.$axios.retry.get(this.$routing.generate('get_unread_messages_count'))
                .then((res) => {
                    this.count = res.data;
                })
                .catch((error) => this.sendLogs('error', 'get unread messages count response error', error));
        },
    },
    mounted() {
        this.loadCount();
    },
};
</script>
