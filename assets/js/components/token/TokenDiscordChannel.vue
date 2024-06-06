<template>
    <div class="row">
        <div
            v-if="editing"
            class="form-group col-12"
        >
            <m-input
                v-model="newDiscord"
                :invalid="showDiscordError"
                :label="$t('token.discord.label')"
            >
                <template v-slot:errors>
                    <div v-if="showDiscordError">
                        {{ $t('token.discord.invalid_url') }}
                    </div>
                </template>
            </m-input>
            <div class="col-12 text-left px-0 d-flex align-items-center">
                <m-button type="primary" :loading="submitting" @click="editDiscord">
                    {{ $t('token.discord.submit') }}
                </m-button>
                <m-button type="link" @click="toggleEdit" class="ml-2">
                    {{ $t('token.discord.cancel') }}
                </m-button>
            </div>
        </div>
        <div
            v-else
            class="col text-truncate"
        >
            <span
                id="discord-link"
                class="c-pointer text-white"
                @click.prevent="toggleEdit"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block mr-2">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'discord'}"
                        size="lg"
                    />
                </span>
                <a href="#" class="link highlight">
                    {{ computedDiscordUrl }}
                </a>
            </span>
        </div>
        <div class="col-auto" v-if="!editing">
            <a
                v-if="currentDiscord && !submitting"
                @click.prevent="deleteDiscord"
            >
                <font-awesome-icon
                    icon="times"
                    class="text-danger c-pointer ml-2"
                />
            </a>
            <div v-if="submitting" class="spinner-border spinner-border-sm" role="status"></div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {faDiscord} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin, NotificationMixin} from '../../mixins/';
import {isValidDiscordUrl} from '../../utils';
import {HTTP_OK, DISCORD_DEFAULT_URL} from '../../utils/constants';
import {MInput, MButton} from '../UI';

library.add(faDiscord, faTimes);

export default {
    name: 'TokenDiscordChannel',
    components: {
        FontAwesomeIcon,
        MInput,
        MButton,
    },
    mixins: [
        FiltersMixin,
        NotificationMixin,
    ],
    props: {
        currentDiscord: String,
        editingDiscord: Boolean,
        tokenName: String,
    },
    data() {
        return {
            editing: this.editingDiscord,
            newDiscord: this.currentDiscord || DISCORD_DEFAULT_URL,
            showDiscordError: false,
            submitting: false,
            updateUrl: this.$routing.generate('token_update', {
                name: this.tokenName,
            }),
        };
    },
    watch: {
        editingDiscord: function() {
            this.submitting = false;
            this.editing = this.editingDiscord;
        },
        newDiscord: function() {
            if (this.newDiscord === DISCORD_DEFAULT_URL) {
                this.showDiscordError = false;
            } else {
                this.checkDiscordUrl();
            }
        },
    },
    computed: {
        computedDiscordUrl: function() {
            return this.currentDiscord || this.$t('token.discord.empty_address');
        },
    },
    methods: {
        editDiscord: function() {
            if (this.newDiscord.length && this.newDiscord !== this.currentDiscord) {
                this.checkDiscordUrl();
            }

            if (this.showDiscordError) {
                return;
            }
            this.saveDiscord('edit');
        },
        checkDiscordUrl: function() {
            this.showDiscordError = !isValidDiscordUrl(this.newDiscord);
        },
        deleteDiscord: function() {
            this.newDiscord = '';
            this.saveDiscord('delete');
        },
        saveDiscord: function(aux) {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.$axios.single.patch(this.updateUrl, {
                discordUrl: this.newDiscord,
            })
                .then((response) => {
                    if (response.status === HTTP_OK) {
                        const state = this.newDiscord ? 'added' : 'deleted';
                        this.$emit('saveDiscord', this.newDiscord);
                        this.newDiscord = this.newDiscord || DISCORD_DEFAULT_URL;
                        this.notifySuccess(this.$t('toasted.success.discord.' + state));
                        this.editing = false;
                    }
                    this.submitting = false;
                })
                .catch((error) => {
                    this.notifyError(error.response.data.message);
                    this.$logger.error('Can not save discord', error);
                    this.submitting = false;
                });
        },
        toggleEdit: function() {
            this.editing = !this.editing;

            if (this.editing) {
                this.$emit('toggleEdit', 'discord');
            }
        },
    },
};
</script>
