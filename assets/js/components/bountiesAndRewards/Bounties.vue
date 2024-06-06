<template>
    <div v-if="showTable">
        <div v-if="!shouldFold" class="card">
            <div
                class="d-flex justify-content-between align-items-center"
                :class="{'p-3': !isSettingPage}"
            >
                <h5>
                    <span
                        :class="[
                          isSettingPage
                            ? 'card-title'
                            : 'font-size-3 font-weight-semibold header-highlighting text-capitalize'
                        ]"
                        v-html="$t('token.bounties.header')"
                    ></span>
                    <guide class="tooltip-title-center">
                        <template slot="header">
                            {{ $t('token.bounties.guide_header') }}
                        </template>
                        <template slot="body">
                            {{ $t('token.bounties.guide_body') }}
                            <coin-avatar
                                :image="tokenAvatar"
                                :is-user-token="true"
                            />
                            {{ tokenName }}.
                        </template>
                    </guide>
                </h5>
                <div
                    v-if="isOwner && isSettingPage"
                    tabindex="0"
                    class="text-white c-pointer"
                    v-b-tooltip="$t('token.tooltip.bounties_add')"
                    @click="openAddModal"
                >
                    <font-awesome-icon
                        :icon="['far', 'plus-square']"
                        class="c-pointer mr-2"
                    />
                    <span class="underline">
                        {{ $t('table.add') }}
                    </span>
                </div>
                <div
                    v-if="isOwner && !isSettingPage"
                    tabindex="0"
                    class="c-pointer"
                >
                    <b-dropdown
                        class="bounty-reward-dropdown mr-n4"
                        text="..."
                        variant="link"
                        toggle-class="bounty-reward-dropdown"
                        menu-class="bounty-reward-dropdown-menu"
                        no-caret
                        dropleft
                    >
                        <b-dropdown-item
                            class="bounty-reward-dropdown-item"
                            @click="openAddModal"
                        >
                            <font-awesome-icon
                                icon="plus"
                                class="mr-2"
                            />
                            <span>
                                {{ $t('token.bounties_add_new_short') }}
                            </span>
                        </b-dropdown-item>
                        <b-dropdown-item
                            :href="linkToPromotion"
                            class="bounty-reward-dropdown-item"
                        >
                            <font-awesome-icon
                                icon="cog"
                                class="mr-2"
                            />
                            <span>
                                {{ $t('token.bounties_manage') }}
                            </span>
                        </b-dropdown-item>
                    </b-dropdown>
                </div>
            </div>
            <div
                class="card-body p-2 h-100 align-items-center justify-content-center"
                :class="{'d-flex' : displayFlex}"
            >
                <template v-if="loaded">
                    <template v-if="isNotEmpty">
                        <b-table
                            class="bounty-table"
                            :fields="fields"
                            :items="itemsToShow"
                            tbody-tr-class="c-pointer"
                            table-class="rewards-table"
                            @row-clicked="openFinalizeOrSummaryModal"
                        >
                            <template v-slot:cell(title)="row">
                                <a
                                    class="link word-break-all reward-td-title"
                                    @click="openFinalizeOrSummaryModal(row.item, $event)"
                                >
                                    <span
                                        class="truncatable"
                                        :ref='`item_${row.item.createdAt}`'
                                        v-b-tooltip.hover
                                        :title="row.item.title"
                                    >
                                        {{ row.item.title }}
                                    </span>
                                </a>
                            </template>
                            <template v-slot:cell(quantity)="row">
                                {{ row.item.activeParticipantsAmount }}/{{ row.item.quantity }}
                            </template>
                            <template v-slot:cell(price)="row">
                                {{ row.value }}
                                <span
                                    class="flex-nowrap text-white pl-1"
                                    v-b-tooltip="tokenName"
                                >
                                    <coin-avatar
                                        :image="tokenAvatar"
                                        :is-user-token="true"
                                    />
                                </span>
                            </template>
                            <template v-if="isOwner && !hideActions" v-slot:cell(actions)="row">
                                <div v-if="actionsLoaded" class="d-flex align-items-center">
                                    <a class="d-flex align-items-center mr-3" @click="$emit('on-edit', row.item)">
                                        <font-awesome-icon :icon="['far', 'edit']" class="mr-1"/>
                                        <span class="link-primary-underscored">{{ $t('table.edit') }}</span>
                                    </a>
                                    <a class="d-flex align-items-center mr-3" @click="$emit('on-delete', row.item)">
                                        <font-awesome-icon :icon="['far', 'window-close']" class="mr-1"/>
                                        <span class="link-primary-underscored">{{ $t('table.delete') }}</span>
                                    </a>
                                    <a class="d-flex align-items-center" @click="$emit('on-summary', row.item)">
                                        <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-1" />
                                        <span class="link-primary-underscored">
                                            {{ $t('bounties_rewards.summary.label') }}
                                        </span>
                                    </a>
                                </div>
                                <font-awesome-icon
                                    v-else
                                    icon="circle-notch"
                                    class="loading-spinner text-white"
                                    fixed-width
                                    spin
                                />
                            </template>
                        </b-table>
                        <div class="d-flex justify-content-center mb-2">
                            <m-button
                                v-if="items.length > 5"
                                type="secondary-rounded"
                                @click="isListOpened = !isListOpened"
                            >
                                {{ showMoreButtonMessage }}
                            </m-button>
                        </div>
                    </template>
                    <template v-else>
                        <span class="text-center py-4">
                            {{ $t('token.bounties.no_rewards') }}
                        </span>
                    </template>
                </template>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon
                            icon="circle-notch"
                            class="loading-spinner text-white"
                            fixed-width
                            spin
                        />
                    </div>
                </template>
            </div>
        </div>
        <div v-else class="card">
            <div class="card-body">
                <div class="text-center">
                    <b-link @click="isFolded = false">
                        <span
                            class="font-size-3 font-weight-semibold header-highlighting"
                            v-html="$t('show', {thing: $t('token.bounties.header')})">
                        </span>
                    </b-link>
                </div>
            </div>
        </div>
        <div v-if="!hideActions" class="pl-2 pt-3">
            <div class="pl-1 pb-1">
                {{ $t('bounties_rewards.manage.table.volunteers.header') }}
                <guide>
                    <template slot="body">
                        {{ $t('bounties_rewards.manage.volunteers.guide') }}
                    </template>
                </guide>
            </div>
            <div v-if="hasVolunteers">
                <b-table
                    :fields="volunteersFields"
                    :items="volunteers"
                    table-class="manage-table"
                >
                    <template v-slot:cell(title)="row">
                        <a
                            class="link word-break-all reward-td-title"
                        >
                            <span
                                class="truncatable"
                                :ref="`item_${row.item.createdAt}`"
                                v-b-tooltip.hover
                                :title="row.item.reward.title"
                            >
                                {{ row.item.reward.title }}
                            </span>
                        </a>
                    </template>
                    <template v-slot:cell(nickname)="row">
                        <a target="_blank" :href="getProfileUrl(row.item.user)">
                            {{ getNickname(row.item.user) }}
                        </a>
                    </template>
                    <template v-slot:cell(note)="row">
                        <span class="text-break">
                            {{ row.item.note }}
                        </span>
                    </template>
                    <template v-slot:cell(createdAt)="row">
                        {{ humanizeDate(row.item.createdAt) }}
                    </template>
                    <template v-slot:cell(actions)="row">
                        <div v-if="!row.item.isRequesting && actionsLoaded">
                            <a
                                class="c-pointer"
                                @click="$emit('accept-volunteer', row.item)"
                            >
                                <font-awesome-icon
                                    :icon="['far', 'check-square']"
                                    class="check-icon c-pointer"
                                />
                                {{ $t('accept') }}
                            </a>
                            <a
                                class="pl-1 c-pointer d-block d-md-inline"
                                @click="$emit('open-volunteer-modal', row.item, 'reject')"
                            >
                                <font-awesome-icon
                                    icon="times"
                                    class="text-danger close-icon c-pointer"
                                />
                                {{ $t('reject') }}
                            </a>
                        </div>
                        <font-awesome-icon
                            v-else
                            icon="circle-notch"
                            class="loading-spinner text-white"
                            fixed-width
                            spin
                        />
                    </template>
                </b-table>
            </div>
            <div v-else class="text-center py-2">
                {{ $t('bounties_rewards.manage.no_volunteers') }}
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {
    faCircleNotch,
    faCog,
    faPlus,
    faEllipsisV,
    faInfoCircle,
} from '@fortawesome/free-solid-svg-icons';
import {faEdit, faWindowClose} from '@fortawesome/fontawesome-free-regular';
import {faPlusSquare} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {
    BLink,
    BTable,
    VBTooltip,
    BDropdown,
    BDropdownItem,
} from 'bootstrap-vue';
import {formatMoney} from '../../utils';
import {
    GENERAL,
    TYPE_BOUNTY,
    BOUNTY,
} from '../../utils/constants';
import Guide from '../Guide';
import {MButton} from '../UI';
import {BountyRewardMixin, UserMixin} from '../../mixins';
import moment from 'moment';
import CoinAvatar from '../CoinAvatar';

library.add(
    faCircleNotch,
    faPlusSquare,
    faWindowClose,
    faEdit,
    faCog,
    faPlus,
    faEllipsisV,
    faInfoCircle,
);

export default {
    name: 'Bounties',
    mixins: [UserMixin, BountyRewardMixin],
    components: {
        Guide,
        BLink,
        BTable,
        FontAwesomeIcon,
        MButton,
        CoinAvatar,
        BDropdown,
        BDropdownItem,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        tokenName: String,
        tokenAvatar: String,
        isOwner: Boolean,
        bounties: Array,
        loaded: Boolean,
        isMobileScreen: Boolean,
        hideActions: Boolean,
        isSettingPage: Boolean,
        actionsLoaded: Boolean,
    },
    data() {
        return {
            isFolded: true,
            isListOpened: false,
            showAddModal: false,
            showVolunteerModal: false,
            isToggleDropdown: false,
            fields: [
                {
                    key: 'title',
                    label: this.$t('rewards.bountie.title'),
                },
                {
                    key: 'quantity',
                    label: this.$t('rewards.bountie.quantity'),
                    tdClass: 'text-right',
                    thClass: 'text-right',
                },
                {
                    key: 'price',
                    label: this.$t('rewards.bountie.reward'),
                    formatter: formatMoney,
                    tdClass: 'reward-td-price text-right pr-3 text-nowrap',
                    thClass: 'text-right pr-3 text-nowrap',
                },
                this.isOwner && !this.hideActions ? {
                    key: 'actions',
                    label: this.$t('table.actions'),
                    thClass: 'w-25',
                } : null,
            ],
            volunteersFields: [
                {
                    key: 'title',
                    label: this.$t('bounties_rewards.manage.table.volunteers.title.label'),
                },
                {
                    key: 'nickname',
                    label: this.$t('bounties_rewards.manage.table.volunteers.nickname.label'),
                },
                {
                    key: 'note',
                    label: this.$t('bounties_rewards.manage.table.volunteers.note.label'),
                    thClass: 'w-25',
                },
                {
                    key: 'createdAt',
                    label: this.$t('bounties_rewards.application_date'),
                    thClass: 'text-nowrap',
                },
                {
                    key: 'actions',
                    label: this.$t('bounties_rewards.manage.table.volunteers.actions.label'),
                    tdClass: 'application-actions',
                },
            ],
        };
    },
    computed: {
        items: function() {
            return this.isSettingPage
                ? this.bounties
                : this.bounties.filter(
                    (bounty) => (!bounty.quantityReached && bounty.quantity > bounty.activeParticipantsAmount));
        },
        itemsToShow: function() {
            return this.isListOpened || 5 >= this.items.length ? this.items : this.items.slice(0, 5);
        },
        showMoreButtonMessage: function() {
            return this.isListOpened
                ? this.$t('token.row_tables.show_less')
                : this.$t('token.row_tables.show_more');
        },
        shouldFold: function() {
            return this.isMobileScreen && this.isFolded;
        },
        isNotEmpty: function() {
            return 0 < this.items.length;
        },
        displayFlex: function() {
            return !this.loaded || !this.isNotEmpty;
        },
        translationsContext: function() {
            return {
                tokenName: this.tokenName,
            };
        },
        volunteers() {
            return this.items.reduce((acc, bounty) => {
                bounty.volunteers.map((volunteer) => {
                    acc.push({
                        ...volunteer,
                        reward: bounty,
                    });
                });
                return acc;
            }, []);
        },
        hasVolunteers() {
            return 0 < this.volunteers.length;
        },
        showTable() {
            return this.isNotEmpty || this.isOwner;
        },
        linkToPromotion() {
            return this.$routing.generate('token_settings', {
                tab: 'promotion',
                tokenName: this.tokenName,
                sub: BOUNTY,
            });
        },
    },
    methods: {
        openAddModal: function() {
            this.$emit('open-add-modal', TYPE_BOUNTY);
        },
        humanizeDate(date) {
            return date
                ? moment.unix(date).format(`${GENERAL.timeFormat} ${GENERAL.dateFormat}`)
                : null;
        },
        toggleDropdown() {
            return this.isToggleDropdown = !this.isToggleDropdown;
        },
    },
};
</script>
