<template>
    <div class="card w-100">
        <modal
            :visible="visible"
            :no-close="submitting"
            dialog-class="align-item-center create-post-modal"
            @close="$emit('close')"
        >
            <template v-slot:header>
                <div class="highlight font-size-3 font-weight-bold text-center" v-html="modalHeader"></div>
            </template>
            <template v-slot:body>
                <m-input
                    v-model="amount"
                    :invalid="invalidAmount"
                    :placeholder="placeholderZero"
                    @keyup="checkInputDot"
                    @keypress="checkInput(subunit)"
                    @paste="checkInput(subunit)"
                >
                    <template v-slot:label>
                        <div class="d-flex align-items-center">
                            <div class="pointer-events-none">
                                {{ $t('post_form.require_amount') }}
                            </div>
                            <guide class="font-size-1 form-control-label-guide">
                                <template slot="body">
                                    {{ $t('post_form.body') }}
                                </template>
                            </guide>
                        </div>
                    </template>
                    <template v-slot:errors>
                        <div v-if="!$v.amount.required">
                            {{ $t('post_form.msg.amount.required') }}
                        </div>
                        <div v-if="!$v.amount.decimal">
                            {{ $t('post_form.msg.amount.numeric') }}
                        </div>
                        <div v-if="!$v.amount.maxDecimals">
                            {{ $t('post_form.msg.amount.max_decimals', {maxDecimals}) }}
                        </div>
                        <div v-if="!$v.amount.between">
                            {{ $t('post_form.msg.amount.between', {maxAmount}) }}
                        </div>
                    </template>
                </m-input>
                <m-input
                    :invalid="invalidShareReward"
                    :placeholder="placeholderZero"
                    v-model="shareReward"
                    @keyup="checkInputDot"
                    @keypress="checkInput(4)"
                    @paste="checkInput(4)"
                >
                    <template v-slot:label>
                        <div class="d-flex align-items-center">
                            <div class="pointer-events-none">
                                {{ $t('post_form.share_reward') }}
                            </div>
                            <guide class="font-size-1 form-control-label-guide">
                                <template slot="body">
                                    {{ $t('post_form.share_reward_guide', { rewardDays }) }}
                                </template>
                            </guide>
                        </div>
                    </template>
                    <template v-slot:errors>
                        <div v-if="!$v.shareReward.required">
                            {{ $t('post_form.msg.share_reward.required') }}
                        </div>
                        <div v-if="!$v.shareReward.decimal">
                            {{ $t('post_form.msg.share_reward.numeric') }}
                        </div>
                        <div v-if="!$v.shareReward.maxDecimals">
                            {{ $t('post_form.msg.share_reward.max_decimals', {maxDecimals}) }}
                        </div>
                        <div v-if="!$v.shareReward.between">
                            {{ $t('post_form.msg.share_reward.between', {maxReward: maxShareReward}) }}
                        </div>
                    </template>
                </m-input>
                <m-input
                    :label="$t('post_form.title')"
                    :invalid="invalidTitle"
                    v-model="title"
                >
                    <template v-slot:errors>
                        <div v-if="invalidTitle && !$v.title.required">
                            {{ $t('post_form.msg.title.required') }}
                        </div>
                        <div v-if="!$v.title.maxLength">
                            {{ $t('post_form.msg.title.max_length', {maxTitleLength}) }}
                        </div>
                    </template>
                </m-input>
                <counted-textarea
                    :label="$t('post_form.content')"
                    v-model="content"
                    :invalid="invalidContent"
                    editable
                >
                    <template v-slot:label>
                        <div class="d-flex align-items-center">
                            <div class="pointer-events-none label-bg-primary-dark">
                                {{ $t('post_form.content') }}
                            </div>
                        </div>
                    </template>
                    <template v-slot:errors>
                        <div v-if="invalidContent && !$v.content.required">
                            {{ $t('post_form.msg.empty') }}
                        </div>
                        <div v-if="!$v.content.minLength">
                            {{ $t('post_form.msg.min_length', {minContentLength}) }}
                        </div>
                        <div v-if="!$v.content.maxLength">
                            {{ $t('post_form.msg.max_length', {maxContentLength}) }}
                        </div>
                    </template>
                </counted-textarea>
                <div class="d-flex justify-content-center align-items-center">
                    <m-button
                        type="primary"
                        class="font-size-2"
                        :disabled="submitting || $v.$invalid"
                        :loading="submitting"
                        @click="savePost"
                    >
                        <template v-slot:prefix>
                            <font-awesome-icon :icon="['far', 'check-square']" class="mr-2"/>
                        </template>
                        {{ post.id ? $t('save') : $t('post.create') }}
                    </m-button>

                    <m-button
                        class="ml-2 font-size-2"
                        @click="cancel"
                    >
                        {{ $t('cancel') }}
                    </m-button>
                </div>
            </template>
        </modal>
        <div
            v-if="showPostForm"
            class="h-100 posts-container col-12 pt-sm-3 px-2"
        >
            <div class="d-flex">
                <m-dropdown
                    v-if="hasMultipleTokens"
                    :label="$t('post_form.token_label')"
                    hide-assistive
                    class="token-avatar-picker mb-4"
                    type="primary"
                >
                    <template v-slot:button-content>
                        <coin-avatar
                            :image="selectedToken.image.avatar_large"
                            image-class="coin-avatar-post"
                            is-user-token
                        />
                        <span class="dropdown-caret"><font-awesome-icon icon="caret-down"/></span>
                    </template>
                    <m-dropdown-item
                        v-for="token in tokens"
                        :key="token.name"
                        :value="token.name"
                        @click="onTokenSelect(token)"
                    >
                        <div class="d-flex">
                            <coin-avatar
                                class="mr-2"
                                :image="token.image.avatar_large"
                                is-user-token
                            />
                            <span class="truncate-block mr-2">
                                {{ token.name }}
                            </span>
                        </div>
                    </m-dropdown-item>
                </m-dropdown>
                <div v-else class="mr-4">
                    <coin-avatar
                        :image="selectedToken ? selectedToken.image.avatar_large : null"
                        image-class="coin-avatar-post"
                        is-user-token
                    />
                </div>
                <div class="w-100 overflow-hidden pr-1">
                    <m-input
                        v-model="title"
                        :label="$t('post_form.title')"
                        :invalid="invalidTitle"
                        class="pt-1"
                        @focus="showContentField = true"
                    >
                        <template v-slot:errors>
                            <div v-if="invalidTitle && !$v.title.required">
                                {{ $t('post_form.msg.title.required') }}
                            </div>
                            <div v-if="!$v.title.maxLength">
                                {{ $t('post_form.msg.title.max_length', {maxTitleLength}) }}
                            </div>
                        </template>
                    </m-input>
                    <counted-textarea
                        v-if="showContentField"
                        v-model="content"
                        :label="$t('post_form.content')"
                        :invalid="invalidContent"
                        :rows="3.5"
                        editable
                    >
                        <template v-slot:label>
                            <div class="d-flex align-items-center">
                                <div class="pointer-events-none label-bg-primary-dark">
                                    {{ $t('post_form.content') }}
                                </div>
                            </div>
                        </template>
                        <template v-slot:errors>
                            <div v-if="invalidContent && !$v.content.required">
                                {{ $t('post_form.msg.empty') }}
                            </div>
                            <div v-if="!$v.content.minLength">
                                {{ $t('post_form.msg.min_length', {minContentLength}) }}
                            </div>
                            <div v-if="!$v.content.maxLength">
                                {{ $t('post_form.msg.max_length', {maxContentLength}) }}
                            </div>
                        </template>
                    </counted-textarea>
                    <div class="d-flex form-control-container mb-2 flex-nowrap">
                        <div
                            v-b-toggle.collapse-options
                            class="text-content-primary my-2 ml-auto mr-2 expand-post-options"
                        >
                            <a href="#" @click.prevent="" v-b-tooltip="getShowHideButtonLabel">
                                <font-awesome-icon
                                    :icon="['fas', 'sliders-h']"
                                />
                            </a>
                        </div>
                        <m-button
                            type="primary"
                            class="font-size-2 mb-2 text-nowrap"
                            :disabled="submitting || $v.$invalid"
                            :loading="submitting"
                            @click="savePost"
                        >
                            <template v-slot:prefix>
                                <font-awesome-icon
                                    :icon="['far', 'check-square']"
                                    class="mr-2"
                                />
                            </template>
                            {{ $t('post.create') }}
                        </m-button>
                    </div>
                    <b-collapse
                        id="collapse-options"
                        class="mt-2"
                        @show="showContentField = true"
                    >
                        <m-input
                            v-model="amount"
                            :invalid="invalidAmount"
                            :placeholder="placeholderZero"
                            @keyup="checkInputDot"
                            @keypress="checkInput(maxDecimals)"
                            @paste="checkInput(maxDecimals)"
                        >
                            <template v-slot:label>
                                <div class="d-flex align-items-center">
                                    <div class="pointer-events-none">
                                        {{ $t('post_form.require_amount') }}
                                    </div>
                                    <guide class="font-size-1 form-control-label-guide">
                                        <template slot="body">
                                            {{ $t('post_form.body') }}
                                        </template>
                                    </guide>
                                </div>
                            </template>
                            <template v-slot:errors>
                                <div v-if="!$v.amount.required">
                                    {{ $t('post_form.msg.amount.required') }}
                                </div>
                                <div v-if="!$v.amount.decimal">
                                    {{ $t('post_form.msg.amount.numeric') }}
                                </div>
                                <div v-if="!$v.amount.maxDecimals">
                                    {{ $t('post_form.msg.amount.max_decimals', {maxDecimals}) }}
                                </div>
                                <div v-if="!$v.amount.between">
                                    {{ $t('post_form.msg.amount.between', {maxAmount}) }}
                                </div>
                            </template>
                        </m-input>
                        <m-input
                            v-model="shareReward"
                            :invalid="invalidShareReward"
                            :placeholder="placeholderZero"
                            @keyup="checkInputDot"
                            @keypress="checkInput(maxDecimals)"
                            @paste="checkInput(maxDecimals)"
                        >
                            <template v-slot:label>
                                <div class="d-flex align-items-center">
                                    <div class="pointer-events-none">
                                        {{ $t('post_form.share_reward') }}
                                    </div>
                                    <guide class="font-size-1 form-control-label-guide">
                                        <template slot="body">
                                            {{ $t('post_form.share_reward_guide', { rewardDays }) }}
                                        </template>
                                    </guide>
                                </div>
                            </template>
                            <template v-slot:errors>
                                <div v-if="!$v.shareReward.required">
                                    {{ $t('post_form.msg.share_reward.required') }}
                                </div>
                                <div v-if="!$v.shareReward.decimal">
                                    {{ $t('post_form.msg.share_reward.numeric') }}
                                </div>
                                <div v-if="!$v.shareReward.maxDecimals">
                                    {{ $t('post_form.msg.share_reward.max_decimals', {maxDecimals}) }}
                                </div>
                                <div v-if="!$v.shareReward.between">
                                    {{ $t('post_form.msg.share_reward.between', {maxReward: maxShareReward}) }}
                                </div>
                            </template>
                        </m-input>
                    </b-collapse>
                </div>
            </div>
        </div>
        <confirm-modal
            :visible="showCreateTokenModal"
            :show-image="false"
            :no-title="true"
            dialog-class="small-modal"
            @confirm="goToTokenCreate"
            @close="showCreateTokenModal = false"
        >
            <template>
                {{ $t('post_create_no_token.modal.description') }}
            </template>
            <template slot="confirm">
                {{ $t('user_feed.create_token.button') }}
            </template>
            <template slot="cancel">
                {{ $t('cancel') }}
            </template>
        </confirm-modal>
    </div>
</template>

<script>
import Modal from './Modal';
import {CountedTextarea, MInput, MButton, MDropdown, MDropdownItem} from '../UI';
import {CheckInputMixin, NotificationMixin} from '../../mixins';
import {toMoney} from '../../utils';
import {HTTP_OK, requiredPostInput} from '../../utils/constants';
import {required, minLength, maxLength, decimal, between} from 'vuelidate/lib/validators';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCheckSquare} from '@fortawesome/free-regular-svg-icons';
import Guide from '../Guide';
import CoinAvatar from '../CoinAvatar';
import {BCollapse, VBToggle, VBTooltip} from 'bootstrap-vue';
import {mapGetters} from 'vuex';
import {faCaretDown, faSlidersH} from '@fortawesome/free-solid-svg-icons';
import ConfirmModal from './ConfirmModal';

library.add(faCheckSquare, faCaretDown, faSlidersH);

const MIN_CONTENT_LENGTH = 2;
const MAX_CONTENT_LENGTH = 1000;
const MAX_DECIMALS = 4;
const MAX_AMOUNT = 999999.9999;
const MAX_SHARE_REWARD = 100;
const MAX_TITLE_LENGTH = 100;

// TODO: Refactor this component
export default {
    name: 'CreatePostModal',
    mixins: [
        CheckInputMixin,
        NotificationMixin,
    ],
    components: {
        Modal,
        MInput,
        MButton,
        MDropdown,
        MDropdownItem,
        CountedTextarea,
        FontAwesomeIcon,
        Guide,
        CoinAvatar,
        BCollapse,
        ConfirmModal,
    },
    directives: {
        'b-toggle': VBToggle,
        'b-tooltip': VBTooltip,
    },
    props: {
        subunit: Number,
        visible: {
            type: Boolean,
            default: true,
        },
        tokenName: String,
        editPost: {
            type: Object,
            default: null,
        },
        tokens: {
            type: Array,
            default: () => [],
        },
        isPostForm: Boolean,
    },
    data() {
        return {
            content: '',
            amount: '',
            title: '',
            shareReward: '',
            submitting: false,
            minContentLength: MIN_CONTENT_LENGTH,
            maxContentLength: MAX_CONTENT_LENGTH,
            maxDecimals: MAX_DECIMALS,
            maxAmount: MAX_AMOUNT,
            maxShareReward: MAX_SHARE_REWARD,
            maxTitleLength: MAX_TITLE_LENGTH,
            placeholderZero: '0',
            selectedToken: this.isPostForm ? this.tokens[0] : null,
            collapsed: false,
            showContentField: false,
            showCreateTokenModal: false,
        };
    },
    mounted() {
        this.$root.$on('bv::collapse::state', (id, collapsed) => {
            if ('collapse-options' === id) {
                this.collapsed = collapsed;
            }
        });

        if (this.selectedToken) {
            this.$emit('token-change', this.selectedToken);
        }
    },
    computed: {
        ...mapGetters('posts', {
            rewardDays: 'getPostRewardsCollectableDays',
        }),
        post() {
            return this.editPost || {
                content: '',
                amount: '',
                title: '',
                shareReward: '',
            };
        },
        modalHeader() {
            return this.post.id ? this.$t('post.edit_modal_title') : this.$t('post.create_modal_title');
        },
        invalidContent() {
            return this.$v.content.$invalid && (0 < this.content.length || this.$v.content.$dirty);
        },
        invalidAmount() {
            return this.$v.amount.$invalid;
        },
        invalidTitle() {
            return this.$v.title.$invalid && (0 < this.title.length || this.$v.title.$dirty);
        },
        invalidShareReward() {
            return this.$v.shareReward.$invalid;
        },
        hasTokens() {
            return 0 < this.tokens.length;
        },
        hasMultipleTokens() {
            return 1 < this.tokens.length;
        },
        showPostForm() {
            return this.isPostForm;
        },
        getShowHideButtonLabel() {
            return this.collapsed
                ? this.$t('post_form.hide_advanced_options_label')
                : this.$t('post_form.show_advanced_options_label');
        },
    },
    methods: {
        async savePost() {
            if (!this.hasTokens) {
                this.showCreateTokenModal = true;

                return;
            }

            this.submitting = true;
            this.$v.$touch();

            if (this.$v.$invalid) {
                return;
            }

            try {
                const url = this.post.id
                    ? this.$routing.generate('edit_post', {tokenName: this.tokenName, id: this.post.id})
                    : this.$routing.generate('create_post',
                        {
                            tokenName: this.isPostForm ? this.selectedToken.name : this.tokenName,
                        });

                const res = await this.$axios.single.post(url, {
                    content: this.content.replace(/&amp;/g, '&'),
                    amount: this.amount || '0',
                    title: this.title,
                    shareReward: this.shareReward || '0',
                });

                if (HTTP_OK !== res.status) {
                    return;
                }

                this.$emit('save-success', {
                    isNew: !this.post.id,
                    post: res.data.post,
                });

                this.notifySuccess(res.data.message);

                this.$emit('close');
                this.reset();
            } catch (error) {
                this.$logger.error('Can\'t create a post', error);
                this.notifyError(error.response.data.message || this.$t('toasted.error.try_later'));
            } finally {
                this.submitting = false;
            }
        },
        reset() {
            this.title = '';
            this.content = '';
            this.amount = '0';
            this.shareReward = '0';
            this.$v.$reset();
        },
        cancel() {
            if (this.submitting) {
                return;
            }

            this.$emit('close');
            this.reset();
        },
        maxDecimalsValidator(val) {
            return (val.split('.')[1] || '').length <= MAX_DECIMALS;
        },
        onTokenSelect(token) {
            this.selectedToken = token;
            this.$emit('token-change', this.selectedToken);
        },
        goToTokenCreate() {
            window.location.href = this.$routing.generate('token_create');
        },
    },
    watch: {
        editPost() {
            this.content = this.editPost?.content || '';
            this.amount = Number(this.editPost?.amount)
                ? toMoney(this.editPost?.amount)
                : '';
            this.title = this.editPost?.title || '';
            this.shareReward = Number(this.editPost?.shareReward)
                ? toMoney(this.editPost?.shareReward)
                : '';
        },
        title() {
            if (0 < this.title.length) {
                this.$v.title.$touch();
            }
        },
        content() {
            if (0 < this.content.length) {
                this.$v.content.$touch();
            }
        },
        visible() {
            this.$v.$reset();
        },
    },
    validations() {
        return {
            content: {
                required: requiredPostInput,
                minLength: minLength(MIN_CONTENT_LENGTH),
                maxLength: maxLength(MAX_CONTENT_LENGTH),
            },
            amount: {
                required: false,
                decimal,
                maxDecimals: this.maxDecimalsValidator,
                between: between(0, MAX_AMOUNT),
            },
            title: {
                required: (val) => required(val.trim()),
                maxLength: maxLength(MAX_TITLE_LENGTH),
            },
            shareReward: {
                required: false,
                decimal,
                maxDecimals: this.maxDecimalsValidator,
                between: between(0, MAX_SHARE_REWARD),
            },
        };
    },
};
</script>
