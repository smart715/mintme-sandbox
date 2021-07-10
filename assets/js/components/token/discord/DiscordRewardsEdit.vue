<template>
    <div>
        <div v-if="!loaded" class="d-flex justify-content-center">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        <div v-else>
            <div v-if="!enabled">
                <a :href="authUrl" class="btn btn-primary btn-block">
                    {{ $t('discord.rewards.bot.invite') }}
                </a>
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
                    <a href="#" class="btn btn-primary btn-block" @click.prevent="openConfirmModal">
                        {{ $t('discord.rewards.bot.remove') }}
                    </a>
                </div>
                <div>
                    {{ $t('discord.rewards.description') }}
                    <guide>
                        <template slot="body">
                            {{ $t('discord.rewards.description.guide') }}
                        </template>
                    </guide>
                </div>
                <div>
                    <div class="custom-control custom-checkbox">
                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="special-roles"
                            v-model="specialRolesEnabled"
                            @input="anyChange = true"
                        >
                        <label for="special-roles" class="custom-control-label">
                            {{ $t('discord.rewards.special_roles') }}
                        </label>
                    </div>
                </div>
                <div>
                    <discord-role-edit
                        class="mt-2"
                        v-for="(role, i) in roles"
                        :key="i"
                        :role="role"
                        :i="i"
                        @update="updateRole"
                        @remove="removeRole"
                    />
                    <div>
                        <button class="btn btn-primary mt-3"
                            :disabled="saving"
                            @click="addRole"
                        >
                            {{ $t('discord.rewards.special_roles.add') }}
                        </button>
                    </div>
                </div>
                <div class="mt-2 text-danger">
                    {{ errorMessage }}
                </div>
                <div v-show="anyChange && !saveDisabled" class="mt-2 text-info">
                    {{ $t('discord.rewards.need_to_save') }}
                </div>
                <div class="my-3 d-flex align-items-center">
                    <button class="btn btn-primary mr-2"
                        :disabled="saveDisabled"
                        @click="save"
                    >
                        {{ $t('save') }}
                    </button>
                    <font-awesome-icon v-show="saving" icon="circle-notch" spin class="loading-spinner" fixed-width />
                </div>
            </div>
        </div>
        <confirm-modal
            :visible="confirmModalVisible"
            @confirm="removeGuild"
            @cancel="closeConfirmModal"
            @close="closeConfirmModal"
        >
            <span class="text-white">
                {{ $t('discord.rewards.guild.remove.confirm_1') }}
                <br>
                {{ guildId }}
                <br>
                {{ $t('discord.rewards.guild.remove.confirm_2') }}
            </span>
        </confirm-modal>
    </div>
</template>

<script>
import Guide from '../../Guide';
import {NotificationMixin, LoggerMixin} from '../../../mixins';
import {toMoney} from '../../../utils';
import DiscordRoleEdit from './DiscordRoleEdit';
import ConfirmModal from '../../modal/ConfirmModal';
import {assertUniquePropertyValuesInObjectArray} from '../../../utils';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

export default {
    name: 'DiscordRewardsEdit',
    mixins: [
        NotificationMixin,
        LoggerMixin,
    ],
    components: {
        Guide,
        DiscordRoleEdit,
        ConfirmModal,
        FontAwesomeIcon,
    },
    props: {
        tokenName: String,
        authUrl: String,
    },
    data() {
        return {
            currentRoles: [],
            newRoles: [],
            removedRoles: [],
            saving: false,
            enabled: false,
            specialRolesEnabled: false,
            loaded: false,
            guildId: null,
            confirmModalVisible: false,
            anyChange: false,
        };
    },
    mounted() {
        this.loadDiscordInfo()
            .then(() => this.loaded = true);
    },
    computed: {
        saveDisabled() {
            return this.$v.$invalid || this.saving;
        },
        roles() {
            return this.currentRoles.concat(this.newRoles);
        },
        errorMessage() {
            if (!this.$v.roles.required && this.specialRolesEnabled) {
                return this.$t('discord.rewards.special_roles.required');
            }

            if (!this.$v.roles.uniqueNames) {
                return this.$t('discord.rewards.special_roles.unique_names');
            }

            if (!this.$v.roles.uniqueBalances) {
                return this.$t('discord.rewards.special_roles.unique_balances');
            }

            return '';
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

                    this.enabled = res.data.config.enabled;
                    this.specialRolesEnabled = res.data.config.specialRolesEnabled;
                    this.guildId = res.data.config.guildId;
                })
                .catch((err) => {
                    this.sendLogs('error', 'can\' load discord info', err);
                    this.notifyError(this.$t('toasted.error.try_later'));

                    throw err;
                });
        },
        addRole() {
            this.newRoles.push({
                id: null,
                name: '',
                color: '',
                requiredBalance: '',
                valid: false,
            });
            this.anyChange = true;
        },
        save() {
            if (this.saveDisabled) {
                return;
            }

            this.saving = true;

            let data = {
                specialRolesEnabled: this.specialRolesEnabled && this.roles.length > 0,
                newRoles: this.newRoles,
                currentRoles: this.currentRoles,
                removedRoles: this.removedRoles,
            };

            this.$axios.single.post(this.$routing.generate('manage_discord_roles', {tokenName: this.tokenName}), data)
                .then((res) => {
                    if (res.data.errors) {
                        this.enabled = res.data.enabled;
                        this.notifyError(res.data.message);
                        return;
                    }

                    return this.loadDiscordInfo().then(() => {
                        this.newRoles = [];
                        this.removedRoles = [];
                        this.saving = false;
                        this.anyChange = false;
                        this.notifySuccess(this.$t('discord.rewards.save.success'));
                    });
                }, (err) => {
                    this.sendLogs('error', 'can\'t save discord rewards', err);
                    this.notifyError(err.response.data.message);
                })
                .finally(() => this.saving = false);
        },
        updateRole(role, property, value) {
            role[property] = value;
            this.$v.$touch();

            if ('valid' !== property) {
                this.anyChange = true;
            }
        },
        removeRole(role) {
            let newRolesIndex = this.newRoles.indexOf(role);

            if (newRolesIndex !== -1) {
                this.newRoles.splice(newRolesIndex, 1);
                return;
            }

            let currentRolesIndex = this.currentRoles.indexOf(role);

            this.currentRoles.splice(currentRolesIndex, 1);

            this.removedRoles.push(role);
            this.anyChange = true;
        },
        removeGuild() {
            return this.$axios.single.delete(this.$routing.generate('remove_guild', {tokenName: this.tokenName}))
                .then(() => {
                    this.enabled = false;
                    this.guildId = null;
                    this.notifySuccess(this.$t('discord.rewards.guild.removed'));
                })
                .catch((err) => {
                    this.sendLogs('error', 'can\'t remove discord guild', err);
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
    watch: {
        roles() {
            if (this.roles.length === 0) {
                this.specialRolesEnabled = false;
            }
        },
    },
    validations() {
        return {
            roles: {
                required: (val) => val.length > 0 || this.removedRoles.length > 0,
                uniqueNames: (arr) => assertUniquePropertyValuesInObjectArray(arr, 'name'),
                uniqueBalances: (arr) => assertUniquePropertyValuesInObjectArray(arr, 'requiredBalance'),
                rolesValid: (arr) => arr.every((item) => item.valid),
            },
        };
    },
};
</script>
