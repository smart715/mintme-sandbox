<template>
    <div class="card">
        <div class="card-header">
            {{ $t('chat.chat_widget.direct_messages') }}
        </div>
        <div class="card-body pb-1">
            <div class="row">
                <div class="contacts-box p-0 px-1 px-lg-0 col-lg-3">
                    <contacts-box
                        :nickname="nickname"
                        :threads-prop="threads"
                        :thread-id-prop="threadId"
                    />
                </div>
                <div class="chat-box p-0 px-1 col-lg-9">
                    <chat-box/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ContactsBox from './ContactsBox';
import ChatBox from './ChatBox';

export default {
    name: 'ChatWidget',
    components: {
        ChatBox,
        ContactsBox,
    },
    props: {
        nickname: String,
        threadId: Number,
        threads: Array,
    },
};
</script>
