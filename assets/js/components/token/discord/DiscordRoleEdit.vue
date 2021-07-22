<template>
    <div>
        <div class="discord-role">
            <div class="name-input">
                <label :for="`name-input-${i}`">
                    {{ $t('discord.rewards.special_roles.name') }}
                </label>
                <br>
                <span v-b-tooltip="tooltipConfig">
                    {{ role.name | truncate(30) }}
                </span>
            </div>
            <div class="color-input">
                <label :for="`color-input-${i}`">
                    {{ $t('discord.rewards.special_roles.color') }}
                </label>
                <br>
                <span>{{ role.color }}</span>
            </div>
            <div class="required-balance-input">
                <label :for="`required-balance-input-${i}`">
                    {{ $t('discord.rewards.special_roles.required_balance') }}
                </label>
                <input
                    :value="role.requiredBalance"
                    class="form-control w-100"
                    :id="`required-balance-input-${i}`"
                    name="requiredBalance"
                    type="text"
                    @input="update('requiredBalance', $event.target.value)"
                    @keypress="checkInput(4, 7)"
                    @paste="checkInput(4, 7)"
                >
            </div>
            <div class="remove-role-button">
                <button class="btn btn-link p-0 delete-icon text-decoration-none text-reset"
                        @click="$emit('remove', role)"
                >
                    <font-awesome-icon
                        class="icon-defaul c-pointer align-middlet"
                        icon="trash"
                        transform="shrink-4 up-1.5"
                    />
                </button>
            </div>
        </div>
        <div class="my-1 text-danger">
            {{ errorMessage }}
        </div>
    </div>
</template>

<script>
import MoneyFilterMixin from '../../../mixins/filters/money';
import {CheckInputMixin, FiltersMixin} from '../../../mixins';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTrash} from '@fortawesome/free-solid-svg-icons';
import {required, decimal, between} from 'vuelidate/lib/validators';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {VBTooltip} from 'bootstrap-vue';

library.add(faTrash);

const minRequiredBalance = 0.0001;
const maxRequiredBalance = 1000000;

export default {
    name: 'DiscordRoleEdit',
    mixins: [
        MoneyFilterMixin,
        CheckInputMixin,
        FiltersMixin,
    ],
    components: {
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        role: Object,
        i: {
            type: Number,
            default: 0,
        },
    },
    computed: {
        errorMessage() {
            if (!this.$v.role.requiredBalance.required && this.role.requiredBalance.length > 0) {
                return this.$t('discord.rewards.special_roles.requiredBalance.not_empty');
            }

            if (!this.$v.role.requiredBalance.decimal && this.role.requiredBalance.length > 0) {
                return this.$t('discord.rewards.special_roles.requiredBalance.decimal');
            }

            if (!this.$v.role.requiredBalance.between && this.role.requiredBalance.length > 0) {
                return this.$t('discord.rewards.special_roles.requiredBalance.between', {min: minRequiredBalance, max: maxRequiredBalance});
            }

            return '';
        },
        valid() {
            return !this.$v.$invalid;
        },
        tooltipConfig() {
            return {
                title: this.role.name,
                boundary: 'window',
                customClass: 'tooltip-custom',
                disabled: this.role.name.length <= 30,
            };
        },
    },
    methods: {
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
                    decimal,
                    between: between(minRequiredBalance, maxRequiredBalance),
                },
            },
        };
    },
};
</script>
