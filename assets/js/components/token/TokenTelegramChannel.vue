<template>
    <div class="row">
        <div
            v-if="editing"
            class="form-group col-12"
        >
            <label for="telegram-err">
                {{ $t('token.telegram.label') }}
            </label>
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
                {{ $t('token.telegram.invalid_url') }}
            </div>
            <div class="col-12 text-left mt-3 px-0">
                <button
                    class="btn btn-primary"
                    @click="editTelegram"
                >
                    {{ $t('token.telegram.submit') }}
                </button>
                <span
                    class="btn-cancel pl-3 c-pointer"
                    @click="editing = false"
                >
                    {{ $t('token.telegram.cancel') }}
                </span>
            </div>
        </div>
        <div
            v-else
            class="col text-truncate"
        >
            <span
                id="telegram-link"
                class="c-pointer text-white hover-icon"
                @click.prevent="toggleEdit"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'telegram'}"
                        size="lg"
                    />
                </span>
                <a href="#" class="text-reset">
                    {{ computedTelegramUrl }}
                </a>
            </span>
            <b-tooltip
                v-if="currentTelegram"
                target="telegram-link"
                :title="computedTelegramUrl"
            />
        </div>
        <div class="col-auto">
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
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {faTelegram} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin, LoggerMixin, NotificationMixin} from '../../mixins/';
import {isValidTelegramUrl} from '../../utils';
import {HTTP_OK} from '../../utils/constants';

library.add(faTelegram, faTimes);

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
    mixins: [FiltersMixin, NotificationMixin, LoggerMixin],
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
            return this.currentTelegram || this.$t('token.telegram.empty_address');
        },
    },
    methods: {
        editTelegram: function() {
            if (this.newTelegram.length && this.newTelegram !== this.currentTelegram) {
                this.checkTelegramUrl();
            }

            if (this.telegramError) {
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
                    if (response.status === HTTP_OK) {
                        let state = this.newTelegram ? 'added' : 'deleted';
                        this.$emit('saveTelegram', this.newTelegram);
                        this.newTelegram = this.newTelegram || 'https://t.me/joinchat/';
                        this.notifySuccess(this.$t('toasted.success.telegram.' + state));
                        this.editing = false;
                    } else {
                        this.$toasted.error(response.data.message || this.$t('toasted.error.network'));
                    }
                    this.submitting = false;
                }, (error) => {
                    this.notifyError(error.response.data.message);
                    this.sendLogs('error', 'Can not save telegram', response);
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
