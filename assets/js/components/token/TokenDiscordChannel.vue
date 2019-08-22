<template>
    <div>
        <div
            v-if="editingDiscord"
            class="form-group my-3"
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
            <div class="col-12 text-left mt-3">
                <button
                    class="btn btn-primary"
                    @click="editDiscord"
                >
                    Save
                </button>
                <span
                    class="btn-cancel pl-3 c-pointer"
                    @click="$emit('toggleEdit', null)"
                >
                    Cancel
                </span>
            </div>
        </div>
        <div
            v-else
            class="d-block mx-0 my-1 p-0"
        >
            <a
                id="discord-link"
                class="c-pointer"
                @click.prevent="$emit('toggleEdit', 'discord')"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'discord'}"
                        size="lg"
                    />
                </span>
                {{ computedDiscordUrl | truncate(35) }}
            </a>
            <b-tooltip
                v-if="currentDiscord"
                target="discord-link"
                :title="computedDiscordUrl"
            />
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
import Toasted from 'vue-toasted';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {faDiscord} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin} from '../../mixins/';
import {isValidDiscordUrl} from '../../utils';

library.add(faDiscord, faTimes);
Vue.use(Toasted, {
    duration: 5000,
    position: 'top-center',
});

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenDiscordChannel',
    props: {
        currentDiscord: String,
        editingDiscord: Boolean,
        updateUrl: String,
    },
    components: {
        FontAwesomeIcon,
    },
    mixins: [FiltersMixin],
    data() {
        return {
            newDiscord: this.currentDiscord || 'https://discord.gg/',
            showDiscordError: false,
            submitting: false,
        };
    },
    watch: {
        editingDiscord: function() {
            this.submitting = false;
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

            if (this.showDiscordError && !this.newDiscord.length) {
                this.showDiscordError = false;
            }
        },
        checkDiscordUrl: function() {
            this.showDiscordError = false;
            if (!isValidDiscordUrl(this.newDiscord)) {
                this.showDiscordError = true;
                return;
            }
            this.saveDiscord();
        },
        deleteDiscord: function() {
            this.newDiscord = '';
            this.saveDiscord();
        },
        saveDiscord: function() {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.$axios.single.patch(this.updateUrl, {
                discordUrl: this.newDiscord,
            })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        let state = this.newDiscord ? 'added' : 'removed';
                        this.$emit('saveDiscord', this.newDiscord);
                        this.newDiscord = this.newDiscord || 'https://discord.gg/';
                        this.$toasted.success(`Discord invitation link ${state} successfully`);
                    } else {
                        this.$toasted.error(response.data.message || 'Network error');
                    }
                    this.submitting = false;
                });
        },
    },
};
</script>
