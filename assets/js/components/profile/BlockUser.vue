<template>
    <div class="col-12 pr-0 pt-2 mt-3 text-center" v-if="blocked || userHasTokens">
        <font-awesome-icon icon="ban"/>
        <a
            href="#"
            :class="{'text-muted text-decoration-none': loading}"
            :title="blockTooltipMsg"
            v-b-tooltip.hover
            @click.prevent="blockAction"
        >
            {{ blockMsg }}
        </a>
        <font-awesome-icon
            v-if="loading"
            class="loading-spinner"
            icon="circle-notch"
            fixed-width
            spin
        />
        <confirm-modal
            :visible="isConfirmVisible"
            :showImage="false"
            @confirm="blockUser"
            @close="isConfirmVisible = false"
        >
            <p class="text-white pt-2">
                {{ $t('profile.block_user.confirm.msg') }}
            </p>
            <b-form-checkbox
                v-model="checkedDeleteActions"
                name="check-button"
            >
                <span class="pl-1">
                    {{ $t('profile.block_user.confirm.msg.option') }}
                </span>
            </b-form-checkbox>
        </confirm-modal>
    </div>
</template>

<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faBan, faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';
import ConfirmModal from '../modal/ConfirmModal';
import {VBTooltip, BFormCheckbox} from 'bootstrap-vue';
import {NotificationMixin} from '../../mixins';

library.add(faBan, faCircleNotch);

export default {
    name: 'BlockUser',
    mixins: [NotificationMixin],
    components: {
        ConfirmModal,
        FontAwesomeIcon,
        BFormCheckbox,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        nickname: String,
        isBlocked: Boolean,
        userHasTokens: Boolean,
    },
    data() {
        return {
            isConfirmVisible: false,
            checkedDeleteActions: true,
            blocked: this.isBlocked,
            loading: false,
        };
    },
    computed: {
        blockMsg() {
            return this.blocked
                ? this.$t('profile.block_user.unban')
                : this.$t('profile.block_user.ban');
        },
        blockTooltipMsg() {
            return this.blocked
                ? this.$t('profile.block_user.unban.tooltip')
                : this.$t('profile.block_user.ban.tooltip');
        },
    },
    methods: {
        blockAction() {
            if (this.loading) {
                return;
            }

            if (this.blocked) {
                this.unblockUser();
            } else {
                this.isConfirmVisible = true;
            }
        },
        async unblockUser() {
            this.loading = true;

            try {
                await this.$axios.single.post(this.$routing.generate('unblock_profile', {nickname: this.nickname}));
                this.blocked = false;
            } catch (err) {
                this.notifyError(this.$t('toasted.error.try_reload'));
                this.$logger.error('Can not unblock profile', err);
            } finally {
                this.loading = false;
            }
        },
        async blockUser() {
            this.loading = true;

            try {
                await this.$axios.single.post(
                    this.$routing.generate('block_profile', {nickname: this.nickname}),
                    {deleteActions: this.checkedDeleteActions}
                );
                this.blocked = true;
            } catch (err) {
                this.notifyError(this.$t('toasted.error.try_reload'));
                this.$logger.error('Can not block profile', err);
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
