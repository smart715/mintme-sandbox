<template>
    <div class="card h-100">
        <div class="card-header px-5 py-4">
            <span class="text-white">
                {{ $t('chat.chat_contacts.contacts') }}
            </span>
        </div>
        <div class="card-body contacts-list p-0 w-100">
            <contacts-list
                v-if="showContactsListWidget"
                :nickname="nickname"
                :thread-id="threadId"
                :contacts="contactsList"
                @change-contact="changeChat"
                @delete-chat-modal="deleteChatModal"
            />
            <div v-else class="row m-0">
                <contacts-dropdown
                    :nickname="nickname"
                    :thread-id="threadId"
                    :contacts="contactsList"
                    @change-contact="changeChat"
                    @delete-chat-modal="deleteChatModal"
                />
            </div>
        </div>
    </div>
</template>

<script>
import ContactsList from './ContactsList';
import ContactsDropdown from './ContactsDropdown';
import {mapMutations, mapGetters} from 'vuex';
import orderBy from 'lodash/orderBy';
import {getRankMedalSrcByNickname} from '../../utils';

export default {
    name: 'ContactsBox',
    props: {
        nickname: String,
        threadIdProp: Number,
        threadsProp: Array,
        topHolders: Array,
    },
    data() {
        return {
            showContactsListWidget: false,
            threads: {},
            firstCall: true,
        };
    },
    components: {
        ContactsDropdown,
        ContactsList,
    },
    computed: {
        ...mapGetters('chat', [
            'getCurrentThreadId',
        ]),
        threadId: {
            get: function() {
                return this.getCurrentThreadId;
            },
            set: function(val) {
                this.setCurrentThreadId(val);
            },
        },
        participants: function() {
            const participants = {};

            Object.values(this.threads).forEach((thread) => {
                thread.metadata.forEach((metadata) => {
                    const participant = metadata.participant;
                    const participantNickname = participant.profile.nickname;

                    if (participantNickname === this.nickname) {
                        return;
                    }

                    const rankImg = getRankMedalSrcByNickname(this.topHolders, participantNickname);

                    participants[participant.id] = {
                        ...participant,
                        threadId: thread.id,
                        tokenName: thread.token.name,
                        lastMessageTimestamp: thread.lastMessageTimestamp,
                        lastMessage: thread.lastMessage,
                        hasUnreadMessages: thread.hasUnreadMessages,
                        isBlocked: metadata.isBlocked,
                        rankImg,
                    };
                });
            });

            return participants;
        },
        contacts: function() {
            const contactList = {};

            Object.values(this.participants).forEach((participant) => {
                contactList[participant.threadId] = {
                    id: participant.id,
                    nickname: participant.profile.nickname,
                    avatar: participant.profile.image.avatar_large,
                    threadId: participant.threadId,
                    tokenName: participant.tokenName,
                    lastMessageTimestamp: participant.lastMessageTimestamp,
                    lastMessage: participant.lastMessage,
                    hasUnreadMessages: participant.hasUnreadMessages,
                    isBlocked: participant.isBlocked,
                    rankImg: participant.rankImg,
                };
            });

            return contactList;
        },
        contactsList: function() {
            return orderBy(this.contacts, ['isBlocked', 'lastMessageTimestamp'], ['asc', 'desc']);
        },
    },
    methods: {
        ...mapMutations('chat', [
            'setContactName',
            'setTokenName',
            'setCurrentThreadId',
            'setRankImg',
        ]),
        initThreads: function() {
            this.threadsProp.forEach((thread) => {
                this.$set(this.threads, thread.id, thread);
            });

            if (0 < this.threadIdProp && this.firstCall) {
                this.changeChat(this.threadIdProp, false);
            }
        },
        changeChat: function(threadId, updateUrl = true) {
            this.firstCall = false;

            if (this.threadId === threadId) {
                return;
            }

            if (updateUrl) {
                history.pushState({
                    threadId,
                }, 'Mintme', this.$routing.generate('chat', {threadId}));
            }

            if (0 < threadId) {
                this.$set(this.threads[threadId], 'hasUnreadMessages', false);
                this.setTokenName(this.contacts[threadId].tokenName);
                this.setContactName(this.contacts[threadId].nickname);
                this.setRankImg(this.contacts[threadId].rankImg);
            }

            this.threadId = threadId;
        },
        listenForUrlChanges: function() {
            window.onpopstate = () => {
                if (history.state && history.state.threadId) {
                    return this.changeChat(history.state.threadId, false);
                }

                this.changeChat(0, false);
            };
        },
        matchScreens: function() {
            const media = window.matchMedia('(min-width: 992px)');
            this.showContactsListWidget = media.matches;
            media.addEventListener('change', (e) => {
                this.showContactsListWidget = e.matches;
            });
        },
        deleteChatModal: function(data) {
            this.$emit('delete-chat-modal', data);
        },
    },
    mounted() {
        this.listenForUrlChanges();
        this.matchScreens();
    },
    watch: {
        threadsProp: function() {
            this.initThreads();
        },
    },
};
</script>
