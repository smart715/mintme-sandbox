<template>
    <div class="card bg-transparent h-100">
        <div class="card-header px-4 py-4 d-flex justify-content-between">
            <div>
                <img
                    v-if="rankImg && messagesLoaded"
                    :src="rankImg"
                    class="chat-box-medal"
                    alt="medal"
                />
                <a
                    v-if="messagesLoaded"
                    :href="profileUrl(contactName)"
                >
                    {{ contactName }}
                </a>
                <span
                    v-else
                    class="text-white"
                >
                    {{ contactName }}
                </span>
            </div>
            <div
                v-if="messagesLoaded"
                class="chat-icons-container"
            >
                <guide>
                    <template slot="icon">
                        <font-awesome-icon
                            :icon="iconBlockUser"
                            class="c-pointer text-light"
                            size="xs"
                            @click="onBlockUser"
                        />
                    </template>
                    <template slot="body">
                        {{ btnBlockUser }}
                    </template>
                </guide>
                <guide>
                    <template slot="icon">
                        <font-awesome-icon
                            :icon="{prefix: 'fa', iconName: 'trash-alt'}"
                            class="c-pointer text-light"
                            size="xs"
                            @click="deleteChatModal()"
                        />
                    </template>
                    <template slot="body">
                        {{ $t('chat.delete_chat.label') }}
                    </template>
                </guide>
            </div>
        </div>
        <div class="card-body p-0">
            <div ref="tableContainer"
                 class="table-responsive fixed-head-table mb-0 h-100"
            >
                <template v-if="!loading">
                    <b-table
                        v-if="hasMessages"
                        class="w-100"
                        thead-class="d-none"
                        tbody-tr-class="mb-2"
                        :items="messagesList"
                        :fields="fields">
                        <template v-slot:cell(trader)="row">
                            <div v-if="row.item.date">
                                <span class="d-block word-break toast-text text-white align-text-top">
                                    - {{ row.item.date }} -
                                </span>
                            </div>
                            <div class="d-flex">
                                <div
                                    class="message"
                                    :class="{ 'message-sended': nickname === row.item.nickname }"
                                >
                                    <div class="w-auto d-flex flex-column">
                                        <span class="word-break pre-line">{{ row.item.body }}</span>
                                        <span class="ml-auto message-hour">{{ row.item.time }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </b-table>
                    <div v-else-if="messagesLoaded">
                        <p class="text-center p-5 message-subtitle">
                            {{ $t('chat.chat_box.no_messages_yet') }}
                        </p>
                    </div>
                    <div v-else>
                        <p class="text-center p-5 message-subtitle">
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
        <div v-if="isChatBlocked(nickname)" class="alert alert-danger" role="alert">
            {{ $t('chat.block_message') }}
        </div>
        <div v-if="showInput" class="card-footer p-0">
            <form @submit.prevent="addMessageToQueue" class="d-flex">
                <textarea
                    class="form-control chat-input px-3 py-3 m-auto"
                    minlength="1"
                    maxlength="500"
                    rows="1"
                    v-model="messageBody"
                    :disabled="isSendDisabled"
                    :placeholder="$t('chat.form.input_placeholder')"
                    @keypress="handleKeypress"
                />
                <button
                    type="submit"
                    class="btn bg-transparent ml-2 px-4 py-0"
                    :disabled="isSendDisabled"
                >
                    <font-awesome-icon
                        :icon="{prefix: 'far', iconName: 'paper-plane'}"
                        class="telegram-icon mr-1"
                        size="2x"
                    />
                </button>
            </form>
        </div>
        <confirm-modal
            :visible="showConfirmBlockModal"
            :show-image="false"
            :no-title="true"
            type="warning"
            @confirm="toggleBlockUser"
            @close="showConfirmBlockModal = false"
        >
            <template>
                <h5> {{ $t('chat.block.confirm_message') }} </h5>
            </template>
            <template slot="confirm">
                {{ $t('chat.block') }}
            </template>
            <template slot="cancel">
                {{ $t('cancel') }}
            </template>
        </confirm-modal>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faBan, faTrashAlt} from '@fortawesome/free-solid-svg-icons';
import {faPaperPlane, faCheckCircle} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {mapGetters} from 'vuex';
import moment from 'moment';
import {BTable} from 'bootstrap-vue';
import {NotificationMixin} from '../../mixins';
import {GENERAL, HTTP_ACCESS_DENIED} from '../../utils/constants';
import Guide from '../Guide';
import ConfirmModal from '../modal/ConfirmModal';

library.add(faCircleNotch, faBan, faTrashAlt, faPaperPlane, faCheckCircle);

const updateMessagesMS = 3500;

export default {
    name: 'ChatBox',
    components: {
        BTable,
        FontAwesomeIcon,
        Guide,
        ConfirmModal,
    },
    mixins: [
        NotificationMixin,
    ],
    props: {
        chatReady: {
            type: Boolean,
            default: true,
        },
        threads: Array,
        nickname: String,
        currentLang: String,
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
            showConfirmBlockModal: false,
        };
    },
    computed: {
        ...mapGetters('chat', {
            getContactName: 'getContactName',
            tokenName: 'getTokenName',
            userTokenName: 'getUserTokenName',
            threadId: 'getCurrentThreadId',
            dMMinAmount: 'getDMMinAmount',
            rankImg: 'getRankImg',
        }),
        ...mapGetters('tradeBalance', {
            quoteFullBalance: 'getQuoteFullBalance',
        }),
        hasMessages: function() {
            return 0 < this.messagesList.length;
        },
        messagesLoaded: function() {
            return null !== this.messages;
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
            const repeatedDate = [];

            return (this.messages || []).map((message) => {
                message.date = moment(message.createdAt).format(GENERAL.dayMonthFormat);

                if (-1 === repeatedDate.indexOf(message.date)) {
                    repeatedDate.push(message.date);
                } else {
                    message.date = null;
                }

                return {
                    id: message.id,
                    nickname: message.sender.profile.nickname,
                    body: message.body,
                    avatar: message.sender.profile.image.avatar_middle,
                    date: message.date,
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
        isSendDisabled: function() {
            return this.isChatBlocked(this.nickname) || this.isChatBlocked(this.contactName);
        },
        thread: function() {
            return this.threads.find((thread) => thread.id === this.threadId);
        },
        participant: function() {
            if (!this.thread) {
                return {};
            }

            const metadata = this.thread.metadata
                .find((metadata) => metadata.participant.profile.nickname === this.contactName);
            return {...metadata.participant, isBlocked: metadata.isBlocked};
        },
        btnBlockUser() {
            return this.participant.isBlocked ? this.$t('chat.unblock_user') : this.$t('chat.block_user');
        },
        iconBlockUser() {
            let icon = {prefix: 'fa', iconName: 'ban'};

            if (this.participant.isBlocked) {
                icon = {prefix: 'far', iconName: 'check-circle'};
            }

            return icon;
        },
    },
    methods: {
        onBlockUser: function() {
            this.participant.isBlocked ? this.toggleBlockUser() : this.openConfirmBlockModal();
        },
        openConfirmBlockModal: function() {
            this.showConfirmBlockModal = true;
        },
        addMessageToQueue: function() {
            if (!this.messageBody) {
                return;
            }

            if (this.tokenName !== this.userTokenName && this.quoteFullBalance < this.dMMinAmount) {
                this.notifyInfo(
                    this.$t('chat.chat_box.min_amount_required_info', this.translationContext)
                );
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
                .then((response) => {
                    if ('blocked' === response.data.status) {
                        this.notifyError(response.data.message);
                        location.reload();

                        return;
                    }

                    this.sendNextMessage();
                })
                .catch((error) => {
                    if (HTTP_ACCESS_DENIED === error.response.status && error.response.data.message) {
                        this.notifyError(error.response.data.message);
                    } else {
                        this.sendMessage(body);
                    }
                    this.$logger.error('send message error', error);
                });
        },
        getMessages: function() {
            this.messages = null;

            if (0 === this.threadId) {
                return;
            }

            this.loading = true;
            this.$axios.retry.get(this.messagesUrl)
                .then((res) => {
                    if (this.messagesUrl === res.config.url) {
                        this.messages = res.data;
                        this.updateMessagesInterval();
                    }
                })
                .catch((error) => this.$logger.error('get messages response error', error))
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
                    }
                })
                .catch((error) => this.$logger.error('update messages response error', error));
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
            const parentDiv = this.$refs.tableContainer;
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
        isChatBlocked: function(nickname) {
            if (!this.threadId) {
                return false;
            }
            const metadata = this.thread.metadata
                .find((metadata) => metadata.participant.profile.nickname === nickname);

            return metadata.isBlocked;
        },
        toggleBlockUser: async function() {
            try {
                const response = await this.$axios.retry.post(this.$routing.generate('block_user'), {
                    threadId: this.threadId,
                    participantId: this.participant.id,
                    isBlocked: this.participant.isBlocked,
                });
                this.notifySuccess(response.data.message);
                window.location.replace(this.$routing.generate('chat'));
            } catch (error) {
                if (HTTP_ACCESS_DENIED === error.response.status && error.response.data.message) {
                    this.notifyError(error.response.data.message);
                } else {
                    this.notifyError(error);
                }
                this.$logger.error('block user error', error);
            }
        },
        handleKeypress: function(event) {
            const {key, ctrlKey, shiftKey} = event;
            const isHotkey = ctrlKey || shiftKey;
            const enterPressed = 'enter' === key.toLowerCase() || '\n' === key;

            if (enterPressed && ctrlKey) {
                const {selectionStart, selectionEnd, value} = event.target;
                const arrCurrentValue = value.split('');
                const firstPartValue = arrCurrentValue.slice(0, selectionStart);
                const secondPartValue = arrCurrentValue.slice(selectionEnd, arrCurrentValue.length);
                const newValue = firstPartValue.concat(['\n'], secondPartValue).join('');
                this.messageBody = newValue;
            }

            if (enterPressed && !isHotkey) {
                this.addMessageToQueue();
                event.preventDefault();
            }
        },
        deleteChatModal: function() {
            const data = {
                isOpen: true,
                threadId: this.threadId,
                participantId: this.participant.id,
            };
            this.$emit('delete-chat-modal', data);
        },
        profileUrl(userProfileName) {
            return this.$routing.generate('profile-view', {nickname: userProfileName});
        },
    },
    mounted() {
        moment.locale(this.currentLang);
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
