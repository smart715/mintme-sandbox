<template>
    <div>
        <b-dropdown
            no-caret
            class="chat-block-menu"
        >
            <template #button-content>
                <b-icon-three-dots-vertical></b-icon-three-dots-vertical>
            </template>
            <b-dropdown-item @click="deleteChatModal()">
                {{ btnDeleteChat }}
            </b-dropdown-item>
            <b-dropdown-item @click="onBlockUser">
                {{ btnBlockUser }}
            </b-dropdown-item>
        </b-dropdown>
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
import {BDropdown, BDropdownItem, BIconThreeDotsVertical} from 'bootstrap-vue';
import {NotificationMixin} from '../../mixins';
import {HTTP_ACCESS_DENIED} from '../../utils/constants';
import ConfirmModal from '../modal/ConfirmModal';

export default {
    name: 'BlockWidget',
    components: {
        BDropdown,
        BDropdownItem,
        BIconThreeDotsVertical,
        ConfirmModal,
    },
    mixins: [
        NotificationMixin,
    ],
    props: {
        threadIdProp: null,
        userIdProp: Number,
        isBlocked: Boolean,
    },
    data() {
        return {
            threadId: this.threadIdProp,
            userId: this.userIdProp,
            showConfirmBlockModal: false,
        };
    },
    computed: {
        btnBlockUser() {
            return this.isBlocked ? this.$t('chat.unblock_user') : this.$t('chat.block_user');
        },
        btnDeleteChat() {
            return this.$t('chat.delete_chat.label');
        },
    },
    methods: {
        onBlockUser: function() {
            this.isBlocked ? this.toggleBlockUser() : this.openConfirmBlockModal();
        },
        openConfirmBlockModal: function() {
            this.showConfirmBlockModal = true;
        },
        toggleBlockUser: function() {
            this.$axios.retry.post(this.$routing.generate('block_user'), {
                threadId: this.threadId,
                participantId: this.userId,
                isBlocked: this.isBlocked,
            })
                .then((response) => {
                    this.notifySuccess(response.data.message);
                    window.location.replace(this.$routing.generate('chat'));
                })
                .catch((error) => {
                    if (HTTP_ACCESS_DENIED === error.response.status && error.response.data.message) {
                        this.notifyError(error.response.data.message);
                    } else {
                        this.notifyError(error);
                    }
                    this.$logger.error('block user error', error);
                });
        },
        deleteChatModal: function() {
            const data = {
                isOpen: true,
                threadId: this.threadId,
                participantId: this.userId,
            };
            this.$emit('delete-chat-modal', data);
        },
    },
};
</script>
