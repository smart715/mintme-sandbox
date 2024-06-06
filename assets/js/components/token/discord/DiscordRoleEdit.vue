<template>
    <div>
        <div class="discord-role my-2 px-3 pt-3">
            <div class="d-flex flex-row w-100 pb-2 mb-1">
                <div class="name-input w-50 overflow-hidden">
                    <label class="font-size-12 line-height-1 text-primary-darker pb-0">
                        {{ $t('discord.rewards.special_roles.name') }}
                    </label>
                    <div class="text-truncate" v-b-tooltip="tooltipConfig">
                        {{ role.name | truncate(30) }}
                    </div>
                </div>
                <div class="color-input ml-2 w-50">
                    <label class="font-size-12 line-height-1 text-primary-darker pb-0">
                        {{ $t('discord.rewards.special_roles.color') }}
                    </label>
                    <div class="text-truncate" :style="getRoleColorStyle(role.color)">
                        {{ role.color }}
                    </div>
                </div>
                <div class="remove-role-button align-self-end pr-1">
                    <button
                        class="btn btn-link px-2 delete-icon text-decoration-none text-reset"
                        @click="$emit('remove', role)"
                    >
                        <font-awesome-icon
                            class="icon-default c-pointer align-middle"
                            icon="trash"
                            transform="shrink-4 up-1.5"
                        />
                    </button>
                </div>
            </div>
            <div>
                <m-input
                    :label="$t('discord.rewards.special_roles.required_balance')"
                    v-model="innerRole.requiredBalance"
                    @input="update('requiredBalance', $event)"
                    @keypress="checkInput(0)"
                    @paste="checkInput(0)"
                >
                    <template v-slot:errors>
                        <div v-if="showUniqueError">
                            {{ $t('discord.rewards.edit.unique') }}
                        </div>
                        <div v-if="errorMessage">
                            {{ errorMessage }}
                        </div>
                    </template>
                    <template v-slot:postfix-icon>
                        <coin-avatar
                            :image="tokenAvatar"
                            is-user-token
                        />
                    </template>
                </m-input>
            </div>
        </div>
    </div>
</template>

<script>
import MoneyFilterMixin from '../../../mixins/filters/money';
import {CheckInputMixin, FiltersMixin} from '../../../mixins';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTrash} from '@fortawesome/free-solid-svg-icons';
import {required, numeric, between} from 'vuelidate/lib/validators';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {VBTooltip} from 'bootstrap-vue';
import Decimal from 'decimal.js';
import {MInput} from './../../UI';
import CoinAvatar from '../../CoinAvatar';

library.add(faTrash);

export default {
    name: 'DiscordRoleEdit',
    mixins: [
        MoneyFilterMixin,
        CheckInputMixin,
        FiltersMixin,
    ],
    components: {
        FontAwesomeIcon,
        MInput,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        role: Object,
        tokenAvatar: String,
        i: {
            type: Number,
            default: 0,
        },
        roles: Array,
        minRequiredBalance: Number,
        maxRequiredBalance: Number,
    },
    data() {
        return {
            showUniqueError: false,
            innerRole: this.role,
        };
    },
    computed: {
        errorMessage() {
            if (!this.$v.role.requiredBalance.required) {
                return this.$t('discord.rewards.special_roles.requiredBalance.not_empty');
            }

            if (!this.$v.role.requiredBalance.numeric && 0 < this.role.requiredBalance.length) {
                return this.$t('discord.rewards.special_roles.requiredBalance.numeric');
            }

            if ((!this.$v.role.requiredBalance.between || '0' === this.$v.role.requiredBalance.$model)
                && 0 < this.role.requiredBalance.length
            ) {
                return this.$t('discord.rewards.special_roles.requiredBalance.between', this.translationsContext);
            }

            return '';
        },
        translationsContext() {
            return {
                min: this.minRequiredBalance + 1,
                max: this.maxRequiredBalance,
            };
        },
        valid() {
            return !this.$v.$invalid;
        },
        tooltipConfig() {
            return {
                title: this.role.name,
                boundary: 'window',
                customClass: 'tooltip-custom',
                disabled: 30 >= this.role.name.length,
            };
        },
        isUniqueBalance() {
            return 2 > this.roles.filter(
                (role) => {
                    const currentBalance = new Decimal(role.requiredBalance || 0);

                    return !currentBalance.isZero()
                        && numeric(this.role.requiredBalance)
                        && currentBalance.equals(this.role.requiredBalance || 0);
                }
            ).length;
        },
    },
    methods: {
        getRoleColorStyle(hex) {
            return `color: ${hex}`;
        },
        update(property, value) {
            this.$emit('update', this.role, property, value);
        },
    },
    watch: {
        role: {
            deep: true,
            handler() {
                if (this.role.valid !== this.valid) {
                    this.update('valid', this.valid);
                }

                this.showUniqueError = !this.isUniqueBalance;
                this.$emit('all-unique', !this.showUniqueError);
            },
        },
        valid: {
            immediate: true,
            handler() {
                this.update('valid', this.valid);
            },
        },
    },
    validations() {
        return {
            role: {
                requiredBalance: {
                    required: (val) => required(val.trim()),
                    numeric,
                    between: between(this.minRequiredBalance + 1, this.maxRequiredBalance),
                },
            },
        };
    },
};
</script>
