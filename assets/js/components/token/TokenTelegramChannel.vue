<template>
    <div class="row">
        <div
            v-if="editing"
            class="form-group col-12"
        >
            <m-input
                v-model="newTelegram"
                :invalid="showTelegramError"
                :label="$t('token.telegram.label')"
            >
                <template v-slot:errors>
                    <div v-if="showTelegramError">
                        {{ $t('token.telegram.invalid_url') }}
                    </div>
                </template>
            </m-input>
            <div class="col-12 text-left px-0 d-flex align-items-center">
                <m-button type="primary" :loading="submitting" @click="editTelegram">
                    {{ $t('token.telegram.submit') }}
                </m-button>
                <m-button type="link" @click="editing = false" class="ml-2">
                    {{ $t('token.telegram.cancel') }}
                </m-button>
            </div>
        </div>
        <div
            v-else
            class="col text-truncate"
        >
            <span
                id="telegram-link"
                class="c-pointer text-white"
                @click.prevent="toggleEdit"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block mr-1">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'telegram'}"
                        size="lg"
                    />
                </span>
                <a href="#" class="link highlight">
                    {{ computedTelegramUrl }}
                </a>
            </span>
        </div>
        <div class="col-auto" v-if="!editing">
            <a
                v-if="currentTelegram && !submitting"
                @click.prevent="deleteTelegram"
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
import {faTelegram} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin, NotificationMixin} from '../../mixins/';
import {isValidTelegramUrl} from '../../utils';
import {HTTP_OK, TELEGRAM_DEFAULT_URL} from '../../utils/constants';
import {MInput, MButton} from '../UI';

library.add(faTelegram, faTimes);

export default {
    name: 'TokenTelegramChannel',
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
        currentTelegram: String,
        editingTelegram: Boolean,
        tokenName: String,
    },
    data() {
        return {
            editing: this.editingTelegram,
            newTelegram: this.currentTelegram || TELEGRAM_DEFAULT_URL,
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
        newTelegram: function() {
            if (this.newTelegram === TELEGRAM_DEFAULT_URL) {
                this.showDiscordError = false;
            } else {
                this.checkTelegramUrl();
            }
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
                    if (response.status === HTTP_OK) {
                        const state = this.newTelegram ? 'added' : 'deleted';
                        this.$emit('saveTelegram', this.newTelegram);
                        this.newTelegram = this.newTelegram || TELEGRAM_DEFAULT_URL;
                        this.notifySuccess(this.$t('toasted.success.telegram.' + state));
                        this.editing = false;
                    } else {
                        this.$toasted.error(response.data.message || this.$t('toasted.error.network'));
                    }
                }, (error) => {
                    this.notifyError(error.response.data.message);
                    this.$logger.error('Can not save telegram', response);
                })
                .finally(() => {
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
