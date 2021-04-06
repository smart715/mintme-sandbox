<template>
    <div class="row">
        <div
            v-if="editing"
            class="form-group col-12"
        >
            <label for="discord-err">{{ $t('token.discord.label') }}</label>
            <input
                id="discord-err"
                v-model="newDiscord"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': showDiscordError }"
                @keyup.enter="checkDiscordUrl"
            >
            <div
                v-if="showDiscordError"
                class="invalid-feedback"
            >
                {{ $t('token.discord.invalid_url') }}
            </div>
            <div class="col-12 text-left mt-3 px-0">
                <button
                    class="btn btn-primary"
                    @click="editDiscord"
                >
                    {{ $t('token.discord.submit') }}
                </button>
                <span
                    class="btn-cancel pl-3 c-pointer"
                    @click="toggleEdit"
                >
                    {{ $t('token.discord.cancel') }}
                </span>
            </div>
        </div>
        <div
            v-else
            class="col text-truncate"
        >
            <span
                id="discord-link"
                class="c-pointer text-white hover-icon"
                @click.prevent="toggleEdit"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'discord'}"
                        size="lg"
                    />
                </span>
                <a href="#" class="text-reset">
                    {{ computedDiscordUrl }}
                </a>
            </span>
            <b-tooltip
                v-if="currentDiscord"
                target="discord-link"
                :title="computedDiscordUrl"
            />
        </div>
        <div class="col-auto">
            <a
                v-if="currentDiscord"
                @click.prevent="deleteDiscord"
            >
                <font-awesome-icon
                    icon="times"
                    class="text-danger c-pointer ml-2"
                />
            </a>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {faDiscord} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin, LoggerMixin, NotificationMixin} from '../../mixins/';
import {isValidDiscordUrl} from '../../utils';
import {HTTP_OK} from '../../utils/constants';

library.add(faDiscord, faTimes);

export default {
    name: 'TokenDiscordChannel',
    props: {
        currentDiscord: String,
        editingDiscord: Boolean,
        tokenName: String,
    },
    components: {
        FontAwesomeIcon,
    },
    mixins: [FiltersMixin, NotificationMixin, LoggerMixin],
    data() {
        return {
            editing: this.editingDiscord,
            newDiscord: this.currentDiscord || 'https://discord.gg/',
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

            if (this.discordError) {
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
                       let state = this.newDiscord ? 'added' : 'deleted';
                       this.$emit('saveDiscord', this.newDiscord);
                       this.newDiscord = this.newDiscord || 'https://discord.gg/';
                       this.notifySuccess(this.$t('toasted.success.discord.' + state));
                       this.editing = false;
                    }
                    this.submitting = false;
                }, (error) => {
                    this.notifyError(error.response.data.message);
                    this.sendLogs('error', 'Can not save discord', response);
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
