<template>
    <div
        @click="goToLogInIfGuest"
    >
        <m-textarea
            :label="$t('comment.input.label')"
            :invalid="contentInvalid"
            :disabled="!isEnoughUserBalance"
            v-model="content"
            :rows="3"
            editable
        >
            <template v-slot:errors>
                <div v-if="contentInvalid">
                    {{ contentError }}
                </div>
            </template>
        </m-textarea>
        <div>
            <m-button
                ref="commentButton"
                type="primary"
                class="mr-1"
                :disabled="submitDisabled"
                :loading="isSubmitting"
                @click="submit"
            >
                {{ isEdit ? $t('save') : $t('comment.add_comment') }}
            </m-button>
            <m-button
                v-if="isEdit"
                type="secondary"
                :disabled="isSubmitting"
                @click="cancel"
            >
                {{ $t('cancel') }}
            </m-button>
        </div>
        <add-phone-alert-modal
            :visible="addPhoneModalVisible"
            :message="addPhoneModalMessage"
            @close="addPhoneModalVisible = false"
            @phone-verified="onPhoneVerified"
        />
    </div>
</template>

<script>
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {NotificationMixin, AddPhoneAlertMixin} from '../../mixins';
import {MTextarea, MButton} from '../UI';
import Decimal from 'decimal.js';
import {mapGetters} from 'vuex';
import AddPhoneAlertModal from '../modal/AddPhoneAlertModal';

export default {
    name: 'CommentForm',
    mixins: [
        NotificationMixin,
        AddPhoneAlertMixin,
    ],
    components: {
        MTextarea,
        MButton,
        AddPhoneAlertModal,
    },
    props: {
        loggedIn: Boolean,
        propContent: {
            type: String,
            default: '',
        },
        apiUrl: String,
        resetAfterSubmit: {
            type: Boolean,
            default: false,
        },
        isEdit: Boolean,
        post: Object,
        commentMinAmount: Number,
        isOwner: Boolean,
    },
    data() {
        return {
            content: this.propContent,
            loginUrl: this.$routing.generate('login', {}, true),
            minContentLength: 2,
            maxContentLength: 500,
            isSubmitting: false,
        };
    },
    computed: {
        ...mapGetters('tradeBalance', {
            quoteBalance: 'getQuoteBalance',
        }),
        ...mapGetters('user', {
            hasPhoneVerified: 'getHasPhoneVerified',
        }),
        contentInvalid() {
            return this.$v.content.$invalid && 0 < this.content.length;
        },
        contentError() {
            if (!this.$v.content.required) {
                return 'Content can\'t be empty';
            }
            if (!this.$v.content.minLength) {
                return `Content must be at least ${this.minContentLength} characters long`;
            }
            if (!this.$v.content.maxLength) {
                return `Content can't be more than ${this.maxContentLength} characters long`;
            }
            return '';
        },
        isEnoughUserBalance() {
            return this.quoteBalance >= this.commentMinAmount;
        },
        submitDisabled() {
            return this.$v.content.$invalid || !this.isEnoughUserBalance;
        },
    },
    methods: {
        submit() {
            if (!this.loggedIn) {
                location.href = this.loginUrl;
                return;
            }

            if (this.$v.content.$invalid || this.isSubmitting) {
                return;
            }

            if (!this.hasPhoneVerified) {
                this.addPhoneModalVisible = true;
                return;
            }

            this.isSubmitting = true;
            this.$axios.single.post(this.apiUrl, {content: this.content})
                .then((res) => {
                    this.$emit('submitted', res.data.comment);

                    if (this.resetAfterSubmit) {
                        this.content = '';
                    }
                })
                .catch((error) => {
                    error.response?.data?.message
                        ? this.notifyError(error.response?.data.message)
                        : this.$emit('error');
                })
                .finally(() => {
                    this.isSubmitting = false;
                });
        },
        cancel() {
            if (!this.loggedIn) {
                location.href = this.loginUrl;
                return;
            }

            this.content = this.propContent;
            this.$emit('cancel');
        },
        goToLogInIfGuest(e) {
            if (!this.loggedIn) {
                e.target.blur();
                location.href = this.loginUrl;
            }

            if (!this.isOwner && new Decimal(this.commentMinAmount).gt(this.quoteBalance)) {
                this.notifyInfo(
                    this.$t('comment.add_comment.min_amount', {
                        amount: this.commentMinAmount,
                        currency: this.post.token.name,
                    })
                );
                return;
            }
        },
        onPhoneVerified() {
            this.addPhoneModalVisible = false;
            this.submit();
        },
    },
    watch: {
        propContent() {
            this.content = this.propContent();
        },
    },
    validations() {
        return {
            content: {
                required: (val) => required(val.trim()),
                minLength: minLength(this.minContentLength),
                maxLength: maxLength(this.maxContentLength),
            },
        };
    },
};
</script>
