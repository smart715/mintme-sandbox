<template>
    <div>
        <div v-if="!loaded" class="d-flex justify-content-center py-3">
            <span class="spinner-border spinner-border-sm">
                <span class="sr-only"> {{ $t('loading') }} </span>
            </span>
        </div>
        <div v-else>
            <div v-if="!enabled">
                <a :href="authUrl" class="btn btn-primary btn-block mt-1">
                    {{ $t('discord.rewards.bot.invite') }}
                </a>
                <div class="py-1">
                    {{ $t('discord.rewards.bot.invite.info') }}
                </div>
                <div v-if="guildId">
                    <p>
                        {{ $t('discord.rewards.warning_1') }}
                    </p>
                    <p>
                        {{ $t('discord.rewards.warning_2') }}
                    </p>
                </div>
            </div>
            <div v-else>
                <div>
                    <div class="d-flex flex-row justify-content-between mb-3">
                        <m-button
                            type="primary"
                            :disabled="saving"
                            :loading="loadingRoles"
                            @click="updateRolesFromDiscord"
                        >
                            {{ $t('discord.rewards.special_roles.add') }}
                        </m-button>
                        <m-button
                            type="primary"
                            class="ml-2"
                            @click.prevent="openConfirmModal"
                        >
                            <template v-slot:prefix>
                                <font-awesome-icon :icon="['fa', 'trash']" class="mr-2" />
                            </template>
                            {{ $t('discord.rewards.bot.remove') }}
                        </m-button>
                    </div>
                    <div v-if="showNewRolesInfo" class="py-1">
                        <span>
                            {{ $t('discord.rewards.bot.new_roles.info', translationContext) }}
                        </span>
                    </div>
                    <div v-if="showHelp" class="d-flex discord-help p-1 mt-4">
                        <span v-html="$t('discord.rewards.special_roles.add.no_found', translationContext)"></span>
                    </div>
                </div>
                <div>
                    <discord-role-edit
                        v-for="(role, i) in roles"
                        :key="i"
                        :role="role"
                        :i="i"
                        :roles="roles"
                        :min-required-balance="balances.min"
                        :max-required-balance="balances.max"
                        :token-avatar="tokenAvatar"
                        @update="updateRole"
                        @remove="removeRole"
                        @all-unique="changeUnique"
                    />
                    <div v-if="showMinValueWarning" class="mt-2 text-danger">
                      {{ $t('discord.rewards.min_token_balance', translationContext) }}
                    </div>
                </div>
                <div class="d-flex align-items-center m-3">
                    <m-button
                        type="primary"
                        :disabled="saveDisabled"
                        :loading="saving"
                        @click="save"
                    >
                        <template v-slot:prefix>
                            <font-awesome-icon :icon="['far', 'check-square']" class="mr-2" />
                        </template>
                        {{ $t('save') }}
                    </m-button>
                </div>
            </div>
        </div>
        <confirm-modal
            :visible="confirmModalVisible"
            type="warning"
            :show-image="false"
            @confirm="removeGuild"
            @cancel="closeConfirmModal"
            @close="closeConfirmModal"
        >
            <span class="text-white">
                {{ $t('discord.rewards.guild.remove.confirm') }}
            </span>
        </confirm-modal>
    </div>
</template>

<script>
import {NotificationMixin} from '../../../mixins';
import {toMoney} from '../../../utils';
import DiscordRoleEdit from './DiscordRoleEdit';
import ConfirmModal from '../../modal/ConfirmModal';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faTrash} from '@fortawesome/free-solid-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';
import Decimal from 'decimal.js';
import {MButton} from '../../UI';
import {faCheckSquare} from '@fortawesome/free-regular-svg-icons';

library.add(faCheckSquare, faTrash);

const minRequiredBalance = 0;
const maxRequiredBalance = 1000000;

export default {
    name: 'DiscordRewardsEdit',
    mixins: [
        NotificationMixin,
    ],
    components: {
        DiscordRoleEdit,
        ConfirmModal,
        FontAwesomeIcon,
        MButton,
    },
    props: {
        tokenName: String,
        tokenAvatar: String,
        authUrl: String,
        loadRoles: Boolean,
    },
    data() {
        return {
            currentRoles: [],
            newRoles: [],
            removedRoles: [],
            saving: false,
            enabled: false,
            loaded: false,
            guildId: null,
            confirmModalVisible: false,
            showHelp: true,
            loadingRoles: false,
            showNewRolesInfo: false,
            showMinValueWarning: false,
            allUniqueValues: true,
            balances: {
                min: minRequiredBalance,
                max: maxRequiredBalance,
            },
        };
    },
    async mounted() {
        try {
            await this.loadDiscordInfo();
            this.loaded = true;

            if (this.loadRoles && this.enabled) {
                await this.updateRolesFromDiscord();

                if (0 !== this.newRoles.length) {
                    this.showNewRolesInfo = true;
                }
            }
        } catch { }
    },
    computed: {
        saveDisabled() {
            return this.$v.$invalid || this.saving || !this.allUniqueValues;
        },
        roles() {
            return this.currentRoles.concat(this.newRoles);
        },
        translationContext() {
            return {
                min: this.balances.min + 1,
                kbUrl: this.$routing.generate('kb_show', {url: 'How-to-manage-discord-roles'}),
            };
        },
    },
    methods: {
        loadDiscordInfo() {
            return this.$axios.single.get(this.$routing.generate('get_discord_info', {tokenName: this.tokenName}))
                .then((res) => {
                    this.currentRoles = res.data.roles.map((role) => {
                        role.requiredBalance = toMoney(role.requiredBalance);
                        role.valid = false;
                        return role;
                    });

                    this.newRoles = [];
                    this.removedRoles = [];
                    this.enabled = res.data.config.enabled;
                    this.guildId = res.data.config.guildId;
                })
                .catch((err) => {
                    this.$logger.error('can\'t load discord info', err);
                    this.notifyError(this.$t('toasted.error.try_later'));

                    throw err;
                });
        },
        updateRolesFromDiscord() {
            this.loadingRoles = true;

            return this.$axios.single.get(this.$routing.generate('update_discord_roles', {tokenName: this.tokenName}))
                .then((res) => {
                    this.currentRoles = res.data.currentRoles.map((role) => {
                        role.requiredBalance = toMoney(role.requiredBalance);
                        role.valid = false;
                        return role;
                    });

                    this.newRoles = res.data.newRoles.map((role) => {
                        role.requiredBalance = toMoney(role.requiredBalance);
                        role.valid = false;
                        return role;
                    });

                    this.showHelp = (this.loadRoles && 0 === this.newRoles.length);
                })
                .catch((err) => {
                    this.$logger.error('can\'t update roles from discord', err);
                })
                .finally(() => {
                    this.loadingRoles = false;
                });
        },
        save() {
            if (this.saveDisabled) {
                return;
            }

            this.saving = true;

            const data = {
                newRoles: this.filterRoles(this.newRoles),
                currentRoles: this.filterRoles(this.currentRoles),
                removedRoles: this.filterRoles(this.removedRoles),
            };

            if (0 === data.newRoles.length && 0 === data.currentRoles.length && 0 === data.removedRoles.length) {
                this.showMinValueWarning = true;
                this.saving = false;

                return;
            } else {
                this.showMinValueWarning = false;
            }

            this.$axios.single.post(this.$routing.generate('manage_discord_roles', {tokenName: this.tokenName}), data)
                .then((res) => {
                    if (res.data.errors) {
                        this.enabled = res.data.enabled;
                        this.notifyError(res.data.message);
                        return;
                    }

                    return this.loadDiscordInfo().then(() => {
                        this.saving = false;
                        this.notifySuccess(this.$t('discord.rewards.save.success'));
                    });
                }, (err) => {
                    this.$logger.error('can\'t save discord rewards', err);
                    this.notifyError(err.response.data.message);
                })
                .finally(() => this.saving = false);
        },
        filterRoles(roles) {
            return roles.filter((role) => !new Decimal(role.requiredBalance).isZero());
        },
        updateRole(role, property, value) {
            role[property] = value;
            this.$v.$touch();
        },
        removeRole(role) {
            const newRolesIndex = this.newRoles.indexOf(role);

            if (-1 !== newRolesIndex) {
                this.newRoles.splice(newRolesIndex, 1);
                return;
            }

            const currentRolesIndex = this.currentRoles.indexOf(role);

            this.currentRoles.splice(currentRolesIndex, 1);

            this.removedRoles.push(role);
        },
        changeUnique(isUniq) {
            this.allUniqueValues = isUniq;
        },
        removeGuild() {
            return this.$axios.single.delete(this.$routing.generate('remove_guild', {tokenName: this.tokenName}))
                .then(() => {
                    this.enabled = false;
                    this.guildId = null;
                    this.notifySuccess(this.$t('discord.rewards.guild.removed'));
                })
                .catch((err) => {
                    this.$logger.error('can\'t remove discord guild', err);
                    this.notifyError(this.$t('discord.rewards.guild.removed.error'));
                });
        },
        openConfirmModal() {
            this.confirmModalVisible = true;
        },
        closeConfirmModal() {
            this.confirmModalVisible = false;
        },
    },
    validations() {
        return {
            roles: {
                required: (val) => 0 < val.length || 0 < this.removedRoles.length,
                rolesValid: (arr) => arr.every((item) => item.valid),
            },
        };
    },
};
</script>
