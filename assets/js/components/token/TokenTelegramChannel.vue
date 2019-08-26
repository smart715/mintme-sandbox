<template>
    <div>
        <div
            v-if="editing"
            class="form-group my-3"
        >
            <label for="telegram-err">Telegram address:</label>
            <input
                id="telegram-err"
                v-model="newTelegram"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': showTelegramError }"
                @keyup.enter="checkTelegramUrl"
            >
            <div
                v-if="showTelegramError"
                class="invalid-feedback"
            >
                Please provide a valid URL.
            </div>
            <div class="col-12 text-left mt-3">
                <button
                    class="btn btn-primary"
                    @click="editTelegram"
                >
                    Save
                </button>
                <span
                    class="btn-cancel pl-3 c-pointer"
                    @click="editing = false"
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
                id="telegram-link"
                class="c-pointer"
                @click.prevent="toggleEdit"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'telegram'}"
                        size="lg"
                    />
                </span>
                {{ computedTelegramUrl | truncate(35) }}
            </a>
            <b-tooltip
                v-if="currentTelegram"
                target="telegram-link"
                :title="computedTelegramUrl"
            />
            <a
                v-if="currentTelegram"
                @click.prevent="deleteTelegram"
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
import {faTelegram} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin} from '../../mixins/';
import {isValidTelegramUrl} from '../../utils';

library.add(faTelegram, faTimes);

Vue.use(Toasted, {
    duration: 5000,
    position: 'top-center',
});

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenTelegramChannel',
    props: {
        currentTelegram: String,
        editingTelegram: Boolean,
        tokenName: String,
    },
    components: {
        FontAwesomeIcon,
    },
    mixins: [FiltersMixin],
    data() {
        return {
            editing: this.editingTelegram,
            newTelegram: this.currentTelegram || 'https://t.me/joinchat/',
            showTelegramError: false,
            submitting: false,
            updateUrl: this.$routing.generate('token_update', {
                name: this.tokenName,
            }),
        };
    },
    watch: {
        editingTelegram: function() {
            this.submitting = false;
            this.editing = this.editingTelegram;
        },
    },
    computed: {
        computedTelegramUrl: function() {
            return this.currentTelegram || 'Add Telegram invitation link';
        },
    },
    methods: {
        editTelegram: function() {
            if (this.newTelegram.length && this.newTelegram !== this.currentTelegram) {
                this.checkTelegramUrl();
            }

            if (this.showTelegramError) {
                return;
            }

            this.saveTelegram();
        },
        checkTelegramUrl: function() {
            this.showTelegramError = !isValidTelegramUrl(this.newTelegram);
        },
        deleteTelegram: function() {
            this.newTelegram = '';
            this.saveTelegram();
        },
        saveTelegram: function() {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.$axios.single.patch(this.updateUrl, {
                telegramUrl: this.newTelegram,
            })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        let state = this.newTelegram ? 'added' : 'removed';
                        this.$emit('saveTelegram', this.newTelegram);
                        this.newTelegram = this.newTelegram || 'https://t.me/joinchat/';
                        this.$toasted.success(`Telegram invitation link ${state} successfully`);
                        this.editing = false;
                    } else {
                        this.$toasted.error(response.data.message || 'Network error');
                    }
                    this.submitting = false;
                });
        },
        toggleEdit: function() {
            this.editing = !this.editing;
            if (this.editing) {
                this.$emit('toggleEdit', 'telegram');
            }
        },
    },
};
</script>
