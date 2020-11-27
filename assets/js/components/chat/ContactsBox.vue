<template>
    <div class="card h-100">
        <div class="card-body p-0">
            <contacts-list
                v-if="showContactsListWidget"
                :nickname="nickname"
                :thread-id="threadId"
                :contacts="contactsList"
                @change-contact="changeChat"
            />
            <div class="row" v-else>
                <contacts-dropdown
                    class="col-12 col-md-6"
                    :nickname="nickname"
                    :thread-id="threadId"
                    :contacts="contactsList"
                    @change-contact="changeChat"
                />
            </div>
        </div>
    </div>
</template>

<script>
import ContactsList from './ContactsList';
import ContactsDropdown from './ContactsDropdown';
import {mapMutations, mapGetters} from 'vuex';

export default {
    name: 'ContactsBox',
    props: {
        nickname: String,
        threadIdProp: Number,
        threads: Array,
    },
    data() {
        return {
            showContactsListWidget: false,
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
            let participants = {};

            this.threads.forEach((thread) => {
                thread.metadata.forEach((metadata) => {
                    if (metadata.participant.profile.nickname === this.nickname) {
                        return;
                    }

                    participants[metadata.participant.id] = {
                        ...metadata.participant,
                        threadId: thread.id,
                        lastMessageTimestamp: thread.lastMessageTimestamp,
                    };
                });
            });

            return participants;
        },
        contacts: function() {
            let contactList = {};

            Object.values(this.participants).forEach((participant) => {
                    contactList[participant.threadId] = {
                        id: participant.id,
                        nickname: participant.profile.nickname,
                        avatar: participant.profile.image.avatar_middle,
                        threadId: participant.threadId,
                        lastMessageTimestamp: participant.lastMessageTimestamp,
                    };
                });

            return contactList;
        },
        contactsList: function() {
            return Object.values(this.contacts)
                .sort((a, b) => b.lastMessageTimestamp - a.lastMessageTimestamp);
        },
    },
    methods: {
        ...mapMutations('chat', [
            'setContactName',
            'setCurrentThreadId',
        ]),
        changeChat: function(threadId, updateUrl = true) {
            if (this.threadId === threadId) {
                return;
            }

            if (updateUrl) {
                history.pushState({
                    threadId,
                }, 'Mintme', this.$routing.generate('chat', {threadId}));
            }

            if (threadId > 0) {
                this.setContactName(this.contacts[threadId].nickname);
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
            let media = window.matchMedia('(min-width: 992px)');
            this.showContactsListWidget = media.matches;
            media.addEventListener('change', (e) => {
                this.showContactsListWidget = e.matches;
            });
        },
    },
    mounted() {
        if (this.threadIdProp > 0) {
            this.changeChat(this.threadIdProp, false);
        }

        this.listenForUrlChanges();
        this.matchScreens();
    },
};
</script>
