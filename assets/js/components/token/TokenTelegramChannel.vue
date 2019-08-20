<template>
    <div>
        <div class="form-group my-3" v-if="editingTelegram">
            <label for="telegram-err">Telegram address:</label>
            <input id="telegram-err"
                type="text"
                v-model="newTelegram"
                class="form-control"
                :class="{ 'is-invalid': showTelegramError }"
                @keyup.enter="checkTelegramUrl">
            <div class="invalid-feedback" v-if="showTelegramError">
                Please provide a valid URL.
            </div>
            <div class="col-12 text-left mt-3">
                <button class="btn btn-primary" @click="editTelegram">Save</button>
                <span class="btn-cancel pl-3 c-pointer" @click="$emit('toggleEdit', null)">
                    Cancel
                </span>
            </div>
        </div>
        <div class="d-block mx-0 my-1 p-0" v-else>
            <a id="telegram-link" class="c-pointer" @click.prevent="$emit('toggleEdit', 'telegram')">
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon :icon="{prefix: 'fab', iconName: 'telegram'}" size="lg" />
                </span>
                {{ computedTelegramUrl | truncate(35) }}
            </a>
            <b-tooltip v-if="currentTelegram" target="telegram-link" :title="computedTelegramUrl" />
            <a v-if="currentTelegram" @click.prevent="deleteTelegram">
                <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
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
        updateUrl: String,
    },
    components: {
        FontAwesomeIcon,
    },
    mixins: [FiltersMixin],
    data() {
        return {
            newTelegram: this.currentTelegram || 'https://t.me/joinchat/',
            showTelegramError: false,
            submitting: false,
        };
    },
    watch: {
        editingTelegram: function() {
            this.submitting = false;
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

            if (this.showTelegramError && !this.newTelegram.length) {
                this.showTelegramError = false;
            }
        },
        checkTelegramUrl: function() {
            this.showTelegramError = false;
            if (!isValidTelegramUrl(this.newTelegram)) {
                this.showTelegramError = true;
                return;
            }
            this.saveTelegram();
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
                    } else {
                        this.$toasted.error(response.data.message || 'Network error');
                    }
                    this.submitting = false;
                });
        },
    },
};
</script>
