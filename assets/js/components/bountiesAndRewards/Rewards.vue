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
                        v-html="$t('token.rewards.header')"
                    ></span>
                    <guide class="tooltip-title-center">
                        <template slot="header">
                            {{ $t('token.rewards.guide_header') }}
                        </template>
                        <template slot="body">
                            <span v-html="$t('token.rewards.guide_body', translationsContext)" />
                        </template>
                    </guide>
                </h5>
                <div
                    v-if="isOwner && isSettingPage"
                    v-b-tooltip.hover="$t('token.tooltip.rewards_add')"
                    tabindex="0"
                    class="text-white c-pointer"
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
                                {{ $t('token.rewards_add_new_short') }}
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
                                {{ $t('token.rewards_manage') }}
                            </span>
                        </b-dropdown-item>
                    </b-dropdown>
                </div>
            </div>
            <div
                class="card-body p-2 h-100 align-items-center justify-content-between"
                :class="{'d-flex': displayFlex}"
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
                                        :ref="`item_${row.item.createdAt}`"
                                        v-b-tooltip.hover
                                        :title="row.item.title"
                                    >
                                        {{ row.item.title }}
                                    </span>
                                </a>
                            </template>
                            <template v-slot:cell(quantity)="row">
                                <div v-if="row.item.quantity">
                                    {{ row.item.activeParticipantsAmount }}/{{ row.item.quantity }}
                                </div>
                                <div v-else>
                                    {{ row.item.activeParticipantsAmount }}/<span class="h5 m-0">&infin;</span>
                                </div>
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
                                        <font-awesome-icon :icon="['fas', 'info-circle']" class="mr-1"/>
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
                        <span class="m-auto py-4">
                            {{ $t('token.reward.no_rewards') }}
                        </span>
                    </template>
                </template>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner text-white" fixed-width />
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
                            v-html="$t('show', {thing: $t('token.rewards.header')})">
                        </span>
                    </b-link>
                </div>
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
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {
    BLink,
    BTable,
    BDropdown,
    BDropdownItem,
    VBTooltip,
} from 'bootstrap-vue';
import {formatMoney, generateCoinAvatarHtml} from '../../utils';
import {
    TYPE_REWARD,
    TOKEN_SHOP,
} from '../../utils/constants';
import Guide from '../Guide';
import {MButton} from '../UI';
import {BountyRewardMixin} from '../../mixins';
import CoinAvatar from '../CoinAvatar';

library.add(
    faCircleNotch,
    faCog,
    faPlus,
    faEllipsisV,
    faInfoCircle,
);

export default {
    name: 'Rewards',
    mixins: [BountyRewardMixin],
    components: {
        Guide,
        BTable,
        BLink,
        FontAwesomeIcon,
        MButton,
        BDropdown,
        BDropdownItem,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        tokenName: String,
        tokenAvatar: String,
        isOwner: Boolean,
        rewards: Array,
        loaded: Boolean,
        actionsLoaded: Boolean,
        isMobileScreen: Boolean,
        hideActions: Boolean,
        isSettingPage: Boolean,
    },
    data() {
        return {
            isFolded: true,
            showAddModal: false,
            isListOpened: false,
            isToggleDropdown: false,
            fields: [
                {
                    key: 'title',
                    label: this.$t('rewards.reward.title'),
                },
                {
                    key: 'quantity',
                    label: this.$t('rewards.reward.quantity'),
                    tdClass: 'text-right',
                    thClass: 'text-right',
                },
                {
                    key: 'price',
                    label: this.$t('rewards.reward.price'),
                    formatter: formatMoney,
                    tdClass: 'reward-td-price text-right pr-3 text-nowrap',
                    thClass: 'text-right pr-3',
                },
                this.isOwner && !this.hideActions ? {
                    key: 'actions',
                    label: this.$t('table.actions'),
                    thClass: 'w-25',
                } : null,
            ],
        };
    },
    computed: {
        items: function() {
            return this.isSettingPage
                ? this.rewards
                : this.rewards.filter((reward) => !reward.quantityReached);
        },
        itemsToShow: function() {
            return this.isListOpened || 5 > this.items.length ? this.items : this.items.slice(0, 5);
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
                tokenAvatar: generateCoinAvatarHtml({image: this.tokenAvatar, isUserToken: true}),
            };
        },
        showTable() {
            return this.isNotEmpty || this.isOwner;
        },
        linkToPromotion() {
            return this.$routing.generate('token_settings', {
                tab: 'promotion',
                tokenName: this.tokenName,
                sub: TOKEN_SHOP,
            });
        },
    },
    methods: {
        openAddModal: function() {
            this.$emit('open-add-modal', TYPE_REWARD);
        },
        toggleDropdown() {
            return this.isToggleDropdown = !this.isToggleDropdown;
        },
    },
};
</script>
