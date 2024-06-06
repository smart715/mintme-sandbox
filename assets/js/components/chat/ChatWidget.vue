<template>
    <div class="card card-fixed-large">
        <balance-init
            v-if="market"
            :is-token="true"
            :websocket-url="websocketUrl"
            :hash="userHash"
            :market-prop="market"
            :logged-in="true"
            :is-owner="false"
            :precision="tokenPrecision"
        />
        <div class="card-body p-0">
            <div class="row m-0">
                <div class="contacts-box p-0 px-1 px-lg-0 col-lg-3">
                    <contacts-box
                        :nickname="nickname"
                        :threads-prop="threads"
                        :thread-id-prop="threadIdProp"
                        :top-holders="topHolders"
                        @delete-chat-modal="deleteChatModal"
                    />
                </div>
                <div class="chat-box p-0 col-lg-9">
                    <chat-box
                        :chat-ready="!!market"
                        :threads="threads"
                        :nickname="nickname"
                        :current-lang="currentLang"
                        @delete-chat-modal="deleteChatModal"
                    />
                </div>
            </div>
        </div>
        <confirm-modal
            :visible="useDeleteChatModal"
            :show-image="false"
            :no-title="true"
            type="warning"
            @confirm="deleteChat"
            @close="closeDeleteChatModal"
        >
            <template>
                <h5> {{ $t('chat.delete_chat.question') }} </h5>
                <h5> {{ $t('chat.delete_chat.warning') }} </h5>
            </template>
            <template slot="confirm">
                {{ $t('chat.delete_chat.button') }}
            </template>
            <template slot="cancel">
                {{ $t('chat.delete_chat.button_cancel') }}
            </template>
        </confirm-modal>
    </div>
</template>

<script>
import BalanceInit from '../trade/BalanceInit';
import ContactsBox from './ContactsBox';
import ChatBox from './ChatBox';
import ConfirmModal from '../modal/ConfirmModal';
import {mapGetters, mapMutations} from 'vuex';
import {HTTP_ACCESS_DENIED} from '../../utils/constants';
import {NotificationMixin} from '../../mixins';

const UPDATE_CONTACT_MS = 10000;

export default {
    name: 'ChatWidget',
    components: {
        BalanceInit,
        ChatBox,
        ContactsBox,
        ConfirmModal,
    },
    mixins: [
        NotificationMixin,
    ],
    props: {
        nickname: String,
        threadIdProp: Number,
        threadsProp: Array,
        dMMinAmount: Number,
        userTokenName: String,
        tokenPrecision: Number,
        websocketUrl: String,
        userHash: String,
        currentLang: String,
        topHolders: Array,
    },
    data() {
        return {
            market: null,
            useDeleteChatModal: false,
            deleteChatData: {
                threadId: null,
                participantId: null,
            },
            threads: [],
        };
    },
    computed: {
        ...mapGetters('chat', {
            threadId: 'getCurrentThreadId',
        }),
    },
    methods: {
        ...mapMutations('chat', [
            'setDMMinAmount',
            'setUserTokenName',
        ]),
        updateMarket: function() {
            this.market = null;

            this.$axios.retry.get(this.$routing.generate('get_thread_market', {
                threadId: this.threadId,
            })).then((res) => {
                this.market = res.data;
            });
        },
        deleteChatModal: function(data) {
            this.deleteChatData.threadId = data.threadId;
            this.deleteChatData.participantId = data.participantId;
            this.useDeleteChatModal = data.isOpen;
        },
        deleteChat: async function() {
            try {
                const response = await this.$axios.retry.post(this.$routing.generate('delete_chat'), {
                    threadId: this.deleteChatData.threadId,
                    participantId: this.deleteChatData.participantId,
                });
                this.useDeleteChatModal = false;
                this.notifySuccess(response.data.message);
                window.location.replace(this.$routing.generate('chat'));
            } catch (error) {
                if (HTTP_ACCESS_DENIED === error.response.status && error.response.data.message) {
                    this.notifyError(error.response.data.message);
                } else {
                    this.notifyError(error);
                }
                this.$logger.error('delete chat error', error);
            }
        },
        closeDeleteChatModal: function() {
            this.useDeleteChatModal = false;
        },
        updateThreads: function() {
            // TODO: short polling here is not the best solution, long polling or websockets should be used
            setInterval(() => this.getContacts(), UPDATE_CONTACT_MS);
        },
        getContacts: async function() {
            try {
                const response = await this.$axios.retry.get(this.$routing.generate('get_contacts'));
                this.threads = response.data.contactList;
            } catch (error) {
                this.$logger.error('get contacts response error', error);
            }
        },
    },
    watch: {
        threadId: function() {
            if (0 < this.threadId) {
                this.updateMarket();
            }
        },
    },
    mounted() {
        this.setDMMinAmount(this.dMMinAmount);
        this.setUserTokenName(this.userTokenName);
        this.threads = this.threadsProp;
        this.updateThreads();
    },
};
</script>
