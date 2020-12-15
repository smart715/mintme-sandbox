<template>
    <div class="card">
        <balance-init
            v-if="market"
            :is-token="true"
            :websocket-url="websocketUrl"
            :hash="userHash"
            :market="market"
            :logged-in="true"
            :is-owner="false"
            :precision="tokenPrecision"
        />
        <div class="card-header">
            {{ $t('chat.chat_widget.direct_messages') }}
        </div>
        <div class="card-body pb-1">
            <div class="row">
                <div class="contacts-box p-0 px-1 px-lg-0 col-lg-3">
                    <contacts-box
                        :nickname="nickname"
                        :threads-prop="threads"
                        :thread-id-prop="threadIdProp"
                    />
                </div>
                <div class="chat-box p-0 px-1 col-lg-9">
                    <chat-box :chat-ready="!!market"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import BalanceInit from '../trade/BalanceInit';
import ContactsBox from './ContactsBox';
import ChatBox from './ChatBox';
import {mapGetters, mapMutations} from 'vuex';

export default {
    name: 'ChatWidget',
    components: {
        BalanceInit,
        ChatBox,
        ContactsBox,
    },
    props: {
        nickname: String,
        threadIdProp: Number,
        threads: Array,
        dMMinAmount: Number,
        userTokenName: String,
        tokenPrecision: Number,
        websocketUrl: String,
        userHash: String,
    },
    data() {
        return {
            market: null,
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
    },
    watch: {
        threadId: function() {
            if (this.threadId > 0) {
                this.updateMarket();
            }
        },
    },
    mounted() {
        this.setDMMinAmount(this.dMMinAmount);
        this.setUserTokenName(this.userTokenName);
    },
};
</script>
