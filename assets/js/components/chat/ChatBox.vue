<template>
    <div class="card">
        <div class="card-header py-2 mb-1">
            {{ contactName }}
        </div>
        <div class="card-body p-0 bg-secondary-dark">
            <div ref="tableContainer" class="table-responsive fixed-head-table mb-0">
                <template v-if="!loading">
                    <b-table
                        v-if="hasMessages"
                        class="w-100"
                        thead-class="d-none"
                        :items="messagesList"
                        :fields="fields">
                        <template v-slot:cell(trader)="row">
                            <div class="d-flex c-pointer flex-row flex-nowrap justify-content-between align-items-start w-100 pb-2 text-white">
                                <img
                                    :src="row.item.avatar"
                                    class="chat-avatar rounded-circle d-block"
                                    alt="avatar">
                                <span class="d-inline-block col">
                                    <span v-if="row.item.date" class="d-block small word-break toast-text text-bold align-text-top">
                                        {{ row.item.date }}
                                    </span>
                                    <br>
                                    <span class="d-block text-bold">
                                        {{ row.item.nickname }}
                                        <span class="small text-bold">
                                            {{ row.item.time }}
                                        </span>
                                    </span>
                                    <span class="d-block small word-break">
                                        {{ row.item.body }}
                                    </span>
                                </span>
                            </div>
                        </template>
                    </b-table>
                    <div v-else-if="messagesLoaded">
                        <p class="text-center p-5">
                            {{ $t('chat.chat_box.no_messages_yet') }}
                        </p>
                    </div>
                    <div v-else>
                        <p class="text-center p-5">
                            {{ $t('chat.chat_box.please_chose_contact') }}
                        </p>
                    </div>
                </template>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
        </div>
        <div v-if="showInput" class="card-footer border-0 py-2 px-0">
            <form @submit.prevent="addMessageToQueue" class="d-flex">
                <input
                    v-model="messageBody"
                    type="text"
                    :placeholder="$t('chat.form.input_placeholder')"
                    class="form-control"
                    minlength="1"
                    maxlength="500"
                >
                <button
                    type="submit"
                    class="btn btn-primary ml-2"
                >
                    {{ $t('chat.form.send') }}
                </button>
            </form>
        </div>
    </div>
</template>

<script>
import {mapGetters} from 'vuex';
import {LoggerMixin, NotificationMixin} from '../../mixins';
import {GENERAL} from '../../utils/constants';
import moment from 'moment';

const updateMessagesMS = 3500;

export default {
    name: 'ChatBox',
    mixins: [
        LoggerMixin,
        NotificationMixin,
    ],
    props: {
        chatReady: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            messagesPage: 1,
            loading: false,
            messageBody: '',
            messages: null,
            messagesQueue: [],
            isQueueRunning: false,
            fields: [
                {
                    key: 'trader',
                    label: 'messages:',
                },
            ],
            updaterId: null,
            repeatedDate: [],
        };
    },
    computed: {
        ...mapGetters('chat', {
            getContactName: 'getContactName',
            tokenName: 'getTokenName',
            userTokenName: 'getUserTokenName',
            threadId: 'getCurrentThreadId',
            dMMinAmount: 'getDMMinAmount',
        }),
        ...mapGetters('tradeBalance', {
            quoteBalance: 'getQuoteBalance',
        }),
        hasMessages: function() {
            return this.messagesList.length > 0;
        },
        messagesLoaded: function() {
            return this.messages !== null;
        },
        showInput: function() {
            return this.messagesLoaded && this.chatReady;
        },
        contactName: function() {
            return this.messagesLoaded
                ? this.getContactName
                : '-';
        },
        messagesUrl: function() {
            return this.$routing.generate('get_messages', {
                threadId: this.threadId,
            });
        },
        newMessagesUrl: function() {
            return this.$routing.generate('get_new_messages', {
                threadId: this.threadId,
                lastMessageId: this.lastMessageId,
            });
        },
        messagesList: function() {
            return (this.messages || []).map((message) => {
                let date = message.createdAt ? moment(message.createdAt).format(GENERAL.dateFormat) : null;

                return {
                    id: message.id,
                    nickname: message.sender.profile.nickname,
                    body: message.body,
                    avatar: message.sender.profile.image.avatar_middle,
                    date: date,
                    time: moment(message.createdAt).format(GENERAL.timeFormat),
                };
            });
        },
        lastMessageId: function() {
            const lastMessage = this.messagesList.slice(-1).pop();
            return lastMessage
                ? lastMessage.id
                : 0;
        },
        translationContext: function() {
            return {
                amount: this.dMMinAmount,
                currency: this.tokenName,
            };
        },
    },
    methods: {
        generateMessages: function() {
            for (const index in this.messages) {
                if (this.repeatedDate.indexOf(this.messages[index].createdAt) === -1) {
                    this.repeatedDate.push(this.messages[index].createdAt);
                } else {
                    this.messages[index].createdAt = null;
                }
            }
        },
        addMessageToQueue: function() {
            if (!this.messageBody) {
                return;
            }

            if (this.tokenName !== this.userTokenName && this.quoteBalance < this.dMMinAmount) {
                this.notifyInfo(this.$t(
                    'chat.chat_box.min_amount_required_info',
                    this.translationContext
                ));
                this.messageBody = '';
                return;
            }

            this.messagesQueue.push(this.messageBody);
            this.messageBody = '';
            this.runQueue();
        },
        runQueue: function() {
            if (this.isQueueRunning) {
                return;
            }

            this.isQueueRunning = true;
            this.sendNextMessage();
        },
        sendNextMessage: function() {
            if (0 === this.messagesQueue.length) {
                this.isQueueRunning = false;
                return;
            }
            this.sendMessage(this.messagesQueue.splice(0, 1)[0]);
        },
        sendMessage: function(body) {
            this.$axios.single.post(this.$routing.generate('send_dm_message', {
                threadId: this.threadId,
            }), {body})
            .then(() => this.sendNextMessage())
            .catch((error) => {
                this.sendMessage(body);
                this.sendLogs('error', 'send message error', error);
            });
        },
        getMessages: function() {
            this.messages = null;

            if (this.threadId === 0) {
                return;
            }

            this.loading = true;
            this.$axios.retry.get(this.messagesUrl)
            .then((res) => {
                if (this.messagesUrl === res.config.url) {
                    this.messages = res.data;
                    this.generateMessages();
                    this.updateMessagesInterval();
                }
            })
            .catch((error) => this.sendLogs('error', 'get messages response error', error))
            .then(() => this.loading = false);
        },
        updateMessages: function() {
            if (!this.messagesLoaded) {
                return;
            }

            this.$axios.single.get(this.newMessagesUrl)
                .then((res) => {
                    if (this.messagesLoaded && this.newMessagesUrl === res.config.url) {
                        this.messages.push(...res.data);
                        this.generateMessages();
                    }
                })
                .catch((error) => this.sendLogs('error', 'update messages response error', error));
        },
        updateMessagesInterval: function() {
            if (this.updaterId) {
                clearInterval(this.updaterId);
            }
            this.updaterId = setInterval(() => {
                this.updateMessages();
            }, updateMessagesMS);
        },
        scrollToDown: function() {
            let parentDiv = this.$refs.tableContainer;
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
    },
    watch: {
        threadId: function() {
            this.getMessages();
        },
        lastMessageId: function() {
            setTimeout(() => {
                this.scrollToDown();
            }, 20);
        },
    },
};
</script>
