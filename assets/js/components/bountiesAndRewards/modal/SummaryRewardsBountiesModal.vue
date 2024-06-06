<template>
    <div>
        <modal
            :visible="visible"
            dialog-class="summary-modal"
            body-class="overflow-auto"
            @close="closeModal"
        >
            <template v-slot:header>
                <span
                    v-if="isLongTitle"
                    v-b-tooltip="title"
                    class="c-pointer"
                >
                    {{ $t('bounties_rewards.summary.header', truncatedTranslationContext) }}
                </span>
                <span v-else class="c-pointer">
                    {{ $t('bounties_rewards.summary.header', translationContext) }}
                </span>
            </template>
            <template v-slot:body>
                <span class="text-break">
                    <div>
                        {{ $t('details') }}
                        <span class="float-right d-flex align-items-center text-center">
                            <a class="link-primary" @click="editItem">
                                <font-awesome-icon
                                    icon="edit"
                                    class="icon-default c-pointer"
                                />
                                <div>{{ $t('edit') }}</div>
                            </a>
                            <a class="ml-2 link-primary" @click="removeItem">
                                <font-awesome-icon
                                    icon="times"
                                    v-b-tooltip="deleteIconHint"
                                    class="text-danger close-icon c-pointer"
                                />
                                <div>{{ $t('delete') }}</div>
                            </a>
                        </span>
                    </div>
                    <div>
                        {{ $t('rewards_bounties.summary.slots') }}: {{ members.length || 0 }} /
                        {{ isBounty ? bountiesMaxLimit : rewardsMaxLimit }}
                    </div>
                    <div>
                        {{ isBounty ? $t('rewards.bountie.reward') : $t('rewards.reward.price') }}:
                        {{ item.price }}
                        <coin-avatar
                            :is-user-token="true"
                            :image="tokenAvatar"
                        />
                        {{ tokenName }}
                    </div>
                    <div>
                        {{ $t('created_on') }}: {{ formattedDate }}
                    </div>
                </span>
                <div class="text-break">
                    {{ item.description }}
                </div>
                <div
                    v-if="hasMembers"
                    class="pt-4 text-break"
                >
                    <b-table
                        :fields="fields"
                        :items="members"
                        :bordered="true"
                        table-class="manage-table table-rewards-summary"
                        :tbody-tr-class="detailsClassFn"
                        details-td-class="pt-0"
                    >
                        <template v-slot:cell(nickname)="row">
                            <a target="_blank" :href="getProfileUrl(row.item.user)">
                                <span
                                    v-if="isLongNickname(row.item.user)"
                                    v-b-tooltip="getNickname(row.item.user)"
                                >
                                    {{ getNickname(row.item.user) | truncate(20) }}
                                </span>
                                <span v-else>
                                    {{ getNickname(row.item.user) }}
                                </span>
                            </a>
                        </template>
                        <template v-slot:cell(createdAt)="row">
                            {{ humanizedDate(row.item.createdAt) }}
                        </template>
                        <template v-slot:head(status)>
                            <div>
                                {{ $t('rewards_bounties.summary.status.label') }}
                                <guide v-if="!isBounty">
                                    <template slot="body">
                                        {{ $t('reward.status.guide') }}
                                    </template>
                                </guide>
                            </div>
                        </template>
                        <template v-slot:cell(status)="row">
                            {{ getHumanizedStatus(row.item.status) }}
                        </template>
                        <template v-if="isBounty" v-slot:cell(actions)="row">
                            <font-awesome-icon
                                v-if="!actionsLoaded"
                                icon="circle-notch"
                                class="loading-spinner text-white"
                                fixed-width
                                spin
                            />
                            <div v-else-if="isPendingMember(row.item)" class="action-status">
                                <a
                                    href="#"
                                    class="pl-3"
                                    @click="showMemberConfirm(row.item)"
                                >
                                    <font-awesome-icon :icon="['far', 'check-square']" class="check-icon c-pointer" />
                                </a>
                                <a
                                    href="#"
                                    class="pl-1"
                                    @click="rejectMember(row.item)"
                                >
                                    <font-awesome-icon icon="times" class="text-danger close-icon c-pointer" />
                                </a>
                            </div>
                        </template>
                        <template v-else v-slot:cell(actions)="row">
                            <font-awesome-icon
                                v-if="!actionsLoaded"
                                icon="circle-notch"
                                class="loading-spinner text-white"
                                fixed-width
                                spin
                            />
                            <div v-else-if="isCompletedMember(row.item)" class="action-status">
                                <a
                                    href="#"
                                    :class="{'disabled': row.item.isRequesting}"
                                    @click="setDelivered(row.item)"
                                >
                                    <font-awesome-icon icon="truck" class="check-icon c-pointer" />
                                    {{ $t('rewards_bounties.summary.actions.set_delivered') }}
                                </a>
                                <a
                                    href="#"
                                    class="ml-2"
                                    :class="{'disabled': row.item.isRequesting}"
                                    @click="refund(row.item)"
                                >
                                    <font-awesome-icon icon="dollar-sign" class="check-icon c-pointer" />
                                    {{ $t('rewards_bounties.summary.actions.refund') }}
                                </a>
                            </div>
                        </template>
                            <template #row-details="row">
                                <template v-if="row.item.note">
                                    {{ $t('rewards_bounties.note') }} {{ row.item.note }}
                                </template>
                            </template>
                    </b-table>
                </div>
                <div v-else-if="isBounty" class="pt-4 text-center">
                    {{ $t('bounties_rewards.manage.no_volunteers') }}
                </div>
                <div v-else class="pt-4 text-center">
                    {{ $t('bounties_rewards.summary.no_participants') }}
                </div>
            </template>
        </modal>
        <confirm-modal
            :visible="showMemberConfirmModal"
            @confirm="acceptVolunteer"
            @close="showMemberConfirmModal = false"
        >
            <span v-html="memberConfirmationMessage"></span>
        </confirm-modal>
    </div>
</template>

<script>
import {faDollarSign, faEdit, faTimes, faTruck, faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {faCheckSquare} from '@fortawesome/free-regular-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {BTable, VBTooltip} from 'bootstrap-vue';
import Modal from '../../modal/Modal';
import ConfirmModal from '../../modal/ConfirmModal';
import {NotificationMixin, UserMixin} from '../../../mixins';
import {
    GENERAL,
    NICKNAME_TRUNCATE_LENGTH,
    REWARD_COMPLETED,
    REWARD_DELIVERED,
    REWARD_NOT_COMPLETED,
    REWARD_PENDING,
    REWARD_REFUNDED,
    REWARD_TITLE_TRUNCATE_LENGTH,
    TYPE_BOUNTY,
} from '../../../utils/constants';
import {mapGetters, mapMutations} from 'vuex';
import {toMoney} from '../../../utils';
import moment from 'moment';
import Guide from '../../Guide';
import CoinAvatar from '../../CoinAvatar';
import TruncateFilterMixin from '../../../mixins/filters/truncate';

const PARTICIPANT_TYPE = 'participant';

library.add(faEdit, faTimes, faCheckSquare, faDollarSign, faTruck, faCircleNotch);

export default {
    name: 'SummaryRewardsBountiesModal',
    mixins: [
        UserMixin,
        NotificationMixin,
        TruncateFilterMixin,
    ],
    directives: {
        'b-tooltip': VBTooltip,
    },
    components: {
        FontAwesomeIcon,
        BTable,
        Modal,
        ConfirmModal,
        Guide,
        CoinAvatar,
    },
    props: {
        visible: Boolean,
        item: Object,
        tokenAvatar: String,
        tokenName: String,
        actionsLoaded: Boolean,
    },
    data() {
        return {
            showMemberConfirmModal: false,
            currentMember: {},
            memberConfirmationMessage: '',
            isRequesting: false,
            fields: [
                {
                    key: 'nickname',
                    label: this.$t('rewards_bounties.summary.nickname.label'),
                },
                {
                    key: 'createdAt',
                    label: this.$t('bounties_rewards.application_date'),
                    thClass: 'text-nowrap',
                },
                {
                    key: 'status',
                    thClass: 'text-nowrap',
                },
                {
                    key: 'actions',
                    label: this.$t('rewards_bounties.summary.actions.label'),
                },
            ],
        };
    },
    computed: {
        ...mapGetters('rewardsAndBounties', {
            rewardsMaxLimit: 'getRewardsMaxLimit',
            bountiesMaxLimit: 'getBountiesMaxLimit',
        }),
        title() {
            return this.item.title;
        },
        quantity() {
            return '(' + (this.visible ? `${this.item.participants.length}/${this.item.quantity}` : '0/0') + ')';
        },
        name() {
            return this.visible ? this.tokenName : '';
        },
        members() {
            const members = [];
            this.item.participants.forEach((participant) => {
                participant.type = PARTICIPANT_TYPE;
                participant._showDetails = true;
                members.push(participant);
            });

            return members.sort((a) => REWARD_PENDING === a.status ? -1 : 1);
        },
        hasMembers() {
            return 0 < this.members.length;
        },
        isBounty() {
            return this.item.type === TYPE_BOUNTY;
        },
        translationContext() {
            return {
                title: this.title,
            };
        },
        truncatedTranslationContext() {
            return {
                title: this.truncateFunc(this.title, REWARD_TITLE_TRUNCATE_LENGTH),
            };
        },
        deleteIconHint() {
            if (!this.item.hasPendingParticipants) {
                return this.$t('delete');
            }

            return this.isBounty
                ? this.$t('reward_bounty.not_completed_bounty')
                : this.$t('reward_bounty.not_completed_reward');
        },
        formattedDate() {
            return moment.unix(this.item.createdAt).format(`${GENERAL.timeFormat} ${GENERAL.dateFormat}`);
        },
        isLongTitle() {
            return this.title.length > REWARD_TITLE_TRUNCATE_LENGTH;
        },
    },
    methods: {
        ...mapMutations('rewardsAndBounties', [
            'editReward',
        ]),
        isPendingMember(member) {
            return REWARD_NOT_COMPLETED === member.status || REWARD_PENDING === member.status;
        },
        isCompletedMember(member) {
            return REWARD_COMPLETED === member.status;
        },
        rejectMember(member) {
            this.$emit('reject-member', {...member, reward: this.item});
        },
        showMemberConfirm(volunteer) {
            this.memberConfirmationMessage = this.$t('bounties_rewards.manage.member.add.confirm', {
                nickname: volunteer.user.profile.nickname,
                amount: toMoney(volunteer.price),
                tokenAvatar: this.tokenAvatar,
            });
            this.showMemberConfirmModal = true;
            this.currentMember = volunteer;
        },
        acceptVolunteer() {
            this.$emit('accept-member', {slug: this.item.slug, memberId: this.currentMember.id});
        },
        closeModal() {
            this.$emit('close');
        },
        editItem() {
            this.$emit('edit');
        },
        removeItem() {
            this.$emit('remove');
        },
        getHumanizedStatus(status) {
            switch (status) {
                case REWARD_NOT_COMPLETED:
                    return this.$t('bounty.status.not_completed');
                case REWARD_REFUNDED:
                    return this.$t('bounty.status.refunded');
                case REWARD_COMPLETED:
                    return this.isBounty ? this.$t('bounty.status.completed') : this.$t('reward.status.in_delivery');
                case REWARD_DELIVERED:
                    return this.$t('reward.status.delivered');
                default:
                    return this.$t('bounty.status.completed');
            }
        },
        refund(member) {
            this.$emit(
                'refund-member',
                {...member, reward: this.item},
            );
        },
        setDelivered(item) {
            const member = this.members.find((m) => m.id === item.id);

            if (member.isRequesting) {
                return;
            }

            this.$set(member, 'isRequesting', true);

            this.$emit(
                'save-participant-status',
                {slug: this.item.slug, member: member, status: REWARD_DELIVERED},
            );

            this.$set(member, 'isRequesting', false);
        },
        humanizedDate(date) {
            return date
                ? moment.unix(date).format(`${GENERAL.timeFormat} ${GENERAL.dateFormat}`)
                : null;
        },
        detailsClassFn(item, rowType) {
            if ('row-details' === rowType) {
                return item.note ? '' : 'hide-note';
            }
        },
        isLongNickname(user) {
            return this.getNickname(user).length > NICKNAME_TRUNCATE_LENGTH;
        },
    },
};
</script>
