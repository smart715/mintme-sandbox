<template>
    <div class="row">
        <div
            v-if="editing"
            class="form-group col-12"
        >
            <label for="discord-err">Discord address:</label>
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
                Please provide a valid URL.
            </div>
            <div class="col-12 text-left mt-3 px-0">
                <button
                    class="btn btn-primary"
                    @click="editDiscord"
                >
                    Save
                </button>
                <span
                    class="btn-cancel pl-3 c-pointer"
                    @click="toggleEdit"
                >
                    Cancel
                </span>
            </div>
        </div>
        <div
            v-else
            class="col text-truncate"
        >
            <a
                id="discord-link"
                class="c-pointer text-white hover-icon"
                @click.prevent="toggleEdit"
                href="#"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'discord'}"
                        size="lg"
                    />
                </span>
                {{ computedDiscordUrl }}
            </a>
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

library.add(faDiscord, faTimes);

const HTTP_ACCEPTED = 202;

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
            return this.currentDiscord || 'Add Discord invitation link';
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
                needToCheckCode: false,
            })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                       let state = this.newDiscord ? 'added' : 'removed';
                       this.$emit('saveDiscord', this.newDiscord);
                       this.newDiscord = this.newDiscord || 'https://discord.gg/';
                       this.notifySuccess(`Discord invitation link ${state} successfully`);
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
