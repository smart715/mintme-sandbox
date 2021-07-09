<template>
    <div>
        <div class="discord-role">
            <div class="name-input">
                <label :for="`name-input-${i}`">
                    {{ $t('discord.rewards.special_roles.name') }}
                </label>
                <input
                    :value="role.name"
                    class="form-control w-100"
                    :id="`name-input-${i}`"
                    name="name"
                    type="text"
                    @input="update('name', $event.target.value)"
                >
            </div>
            <div class="color-input">
                <label :for="`color-input-${i}`">
                    {{ $t('discord.rewards.special_roles.color') }}
                </label>
                <input
                    :value="role.color"
                    class="form-control w-100"
                    :id="`color-input-${i}`"
                    name="color"
                    type="text"
                    @input="update('color', $event.target.value)"
                >
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
import {CheckInputMixin} from '../../../mixins';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTrash} from '@fortawesome/free-solid-svg-icons';
import {required, maxLength, decimal, between} from 'vuelidate/lib/validators';
import {hex} from '../../../utils/constants';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faTrash);

const maxNameLength = 40;
const minRequiredBalance = 0.0001;
const maxRequiredBalance = 1000000;

export default {
    name: 'DiscordRoleEdit',
    mixins: [
        MoneyFilterMixin,
        CheckInputMixin,
    ],
    components: {
        FontAwesomeIcon,
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
            if (!this.$v.role.name.required && this.role.name.length > 0) {
                return this.$t('discord.rewards.special_roles.name.not_empty');
            }

            if (!this.$v.role.name.maxLength) {
                return this.$t('discord.rewards.special_roles.name.max_length', {max: maxNameLength});
            }

            if (!this.$v.role.requiredBalance.required && this.role.requiredBalance.length > 0) {
                return this.$t('discord.rewards.special_roles.requiredBalance.not_empty');
            }

            if (!this.$v.role.requiredBalance.decimal && this.role.requiredBalance.length > 0) {
                return this.$t('discord.rewards.special_roles.requiredBalance.decimal');
            }

            if (!this.$v.role.requiredBalance.between && this.role.requiredBalance.length > 0) {
                return this.$t('discord.rewards.special_roles.requiredBalance.between', {min: minRequiredBalance, max: maxRequiredBalance});
            }

            if (!this.$v.role.color.hex && this.role.color.length > 0) {
                return this.$t('discord.rewards.special_roles.color.hex');
            }

            return '';
        },
        valid() {
            return !this.$v.$invalid;
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
                name: {
                    required: (val) => required(val.trim()),
                    maxLength: maxLength(maxNameLength),
                },
                requiredBalance: {
                    required: (val) => required(val.trim()),
                    decimal,
                    between: between(minRequiredBalance, maxRequiredBalance),
                },
                color: {
                    required: (val) => required(val.trim()),
                    hex,
                },
            },
        };
    },
};
</script>
