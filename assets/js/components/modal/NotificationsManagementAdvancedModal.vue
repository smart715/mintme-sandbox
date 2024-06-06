<template>
    <modal
        :visible="showModal"
        :no-close="true"
        @close="closeModal"
    >
        <template slot="header">
            {{ $t('userNotification.config.advanced.title') }}
        </template>
        <template slot="body">
            <div
                ref="table"
                class="table-responsive table-restricted no-head-table fixed-height-table"
            >
                <div v-if="isLoaded">
                    <div v-if="hasTokens">
                        <div class="d-flex pb-3">
                            <div class="d-flex col-md-8 py-4 pb-md-0">
                                {{ $t('userNotification.config.advanced.description') }}
                            </div>
                            <div class="d-flex flex-row-reverse container-input-search
                                justify-content-center justify-content-md-start">
                                <m-input
                                    v-model="searchPhrase"
                                    class="no-spacer"
                                    :label="$t('trading.search.input')"
                                    @keyup="handleKeyUp($event)"
                                >
                                    <template v-slot:postfix-icon>
                                        <span class="p-1 text-primary">
                                            <font-awesome-icon icon="search" />
                                        </span>
                                    </template>
                                </m-input>
                            </div>
                        </div>
                        <div class="advanced-modal custom-scrollbar">
                            <div
                                v-for="(token, key) in searchResultTokens"
                                :key="token.name"
                            >
                                <div class="my-2">
                                    <div class="card-body py-1">
                                        <div class="mb-2 d-flex justify-content-between">
                                            <div class="d-flex">
                                                <coin-avatar
                                                    :image="generateTokenImage(token)"
                                                    :symbol="token.cryptoSymbol"
                                                    :isUserToken="true"
                                                    class="qt-coin-avatar"
                                                />
                                                <span
                                                    class="text-truncate width-token-name"
                                                    v-b-tooltip="truncateTokenName(token.name).tooltip"
                                                >
                                                    {{ truncateTokenName(token.name).name }}
                                                </span>
                                            </div>
                                            <b-form-checkbox
                                                :ref="token.name"
                                                v-model="config.new_post.channels.advanced[key].value"
                                                class="float-right checkbox-dashed-onfocus no-top"
                                                :disabled="disabledCheckboxSettings"
                                                size="lg"
                                                name="check-button"
                                                switch
                                                tabindex="0"
                                                @change="saveConfig(token.name)"
                                            />
                                            <div
                                                :ref="token.name"
                                                class="d-none pr-4"
                                            >
                                                <div class="spinner-border spinner-border-sm" role="status">
                                                    <span class="sr-only">{{ $t('loading') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        {{ $t('userNotification.config.advanced.no_token') }}
                    </div>
                </div>
                <div v-else class="text-center">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="sr-only">{{ $t('loading') }}</span>
                    </div>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';
import {BFormCheckbox, VBTooltip} from 'bootstrap-vue';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {TOKEN_DEFAULT_ICON_URL} from '../../utils/constants';
import {MInput} from '../UI';
import {FiltersMixin, NotificationMixin} from '../../mixins';
import CoinAvatar from '../CoinAvatar';

const MEDIA_BREAKPOINT = {
    xs: {
        width: 320,
        truncateLimit: 12,
    },
    sm: {
        width: 375,
        truncateLimit: 18,
    },
    sl: {
        width: 425,
        truncateLimit: 21,
    },
    md: {
        width: 768,
        truncateLimit: 55,
    },
};
export default {
    name: 'NotificationsManagementAdvancedModal',
    mixins: [
        FiltersMixin,
        NotificationMixin,
    ],
    components: {
        Modal,
        BFormCheckbox,
        MInput,
        CoinAvatar,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        notificationAdvancedModalVisible: Boolean,
        configProp: Object,
        savingConfigData: Boolean,
    },
    data() {
        return {
            showModal: this.notificationAdvancedModalVisible,
            tokenFields: [
                {
                    key: 'name',
                },
            ],
            isTokenFetched: false,
            config: this.configProp,
            isLoaded: false,
            searchPhrase: '',
            searchPhraseMinLength: 3,
            searchResultTokens: [],
            tokens: [],
            truncateLimit: 0,
            disabledCheckboxSettings: false,
        };
    },
    created() {
        this.handleResize();
        window.addEventListener('resize', this.handleResize);
    },
    beforeDestroy() {
        window.removeEventListener('resize', this.handleResize);
    },
    mounted() {
        this.initUserAdvancedConfig();
        this.resetSearchResultTokens();
    },
    computed: {
        hasTokens: function() {
            return 0 < Object.values(this.config.new_post.channels.advanced || {}).length;
        },
        defaultTokenImage: function() {
            return require(TOKEN_DEFAULT_ICON_URL);
        },
    },
    methods: {
        handleResize() {
            const windowWidth = window.innerWidth;
            const breakpoints = Object.values(MEDIA_BREAKPOINT);

            this.truncateLimit = this.getLimitTruncateFunc(windowWidth, breakpoints);
        },
        truncateTokenName(tokenName) {
            const maxLength = tokenName.length > this.truncateLimit;

            return {
                name: this.truncateFunc(tokenName, this.truncateLimit),
                tooltip: maxLength
                    ? this.tooltipConfig(tokenName)
                    : '',
            };
        },
        tooltipConfig(tokenName) {
            return {
                title: tokenName,
                boundary: 'window',
                customClass: 'tooltip-custom',
            };
        },
        closeModal: function() {
            this.$emit('close');
        },
        fetchTokens: async function() {
            try {
                const res = await this.$axios.retry.get(this.$routing.generate('tokens', {tokensInfo: true}));
                const tokensData = res.data;
                this.tokens = this.fillTokensWithInfo(tokensData.common, tokensData.tokensInfo);
                this.updateTokensConfig();
                this.syncConfigTokens();
                this.isLoaded = true;
                this.resetSearchResultTokens();
            } catch (err) {
                this.$logger.error('Error while fetching tokens in notification mng modal', err);
                this.notifyError(this.$t('toasted.error.can_not_connect'));
            }
        },
        initUserAdvancedConfig: async function() {
            if (!this.isTokenFetched) {
                await this.fetchTokens();
            }

            this.isTokenFetched = true;
        },
        fillTokensWithInfo: function(tokens, tokensInfo) {
            if (!tokens || !tokensInfo) {
                return [];
            }

            return Object.keys(tokens).map((tokenName) => ({
                image: tokensInfo[tokenName]?.image || null,
                name: tokenName,
                value: true,
            }));
        },
        hideAndShowCheckbox: function() {
            const parentElement = this.$refs[this.currentIdElementSettings];

            const checkboxSettings = parentElement[0].$el.classList;
            const spinnerSettings = parentElement[1].classList;

            if (this.savingConfigData) {
                checkboxSettings.add('d-none');
                spinnerSettings.remove('d-none');
                return;
            }

            checkboxSettings.remove('d-none');
            spinnerSettings.add('d-none');
        },
        saveConfig: function(elementId) {
            this.disabledCheckboxSettings = true;
            this.currentIdElementSettings = elementId;

            this.$emit('save-config', {
                advancedConfig: this.config,
            });
        },
        searchPhraseInvalidLength: function(searchPhrase) {
            return searchPhrase.length < this.searchPhraseMinLength
                && 0 < searchPhrase.length;
        },
        handleKeyUp: function() {
            if (!this.searchPhraseInvalidLength(this.searchPhrase)) {
                this.toggleSearch();
            }
        },
        toggleSearch: function() {
            this.resetSearchResultTokens();
            if (!this.searchPhrase) {
                return;
            }

            this.searchResultTokens = this.searchResultTokens.filter((item) => {
                const itemName = item.name.toLowerCase();
                const searchPhrase = this.searchPhrase.toLowerCase();

                return itemName.includes(searchPhrase);
            });
        },
        resetSearchResultTokens: function() {
            this.searchResultTokens = this.config.new_post.channels.advanced;
        },
        updateTokensConfig: function() {
            const tokenConfig = this.config.new_post.channels.advanced;

            if (!tokenConfig || !this.hasTokens) {
                return;
            }

            tokenConfig.forEach((token, key) => {
                if (false === token.value) {
                    this.tokens[key].value = false;
                }
            });
        },
        syncConfigTokens: function() {
            this.config.new_post.channels.advanced = this.tokens;
        },
        generateTokenImage: function(token) {
            return token?.image?.avatar_small ?? this.defaultTokenImage;
        },
    },
    watch: {
        savingConfigData: function(newValue) {
            if (!newValue) {
                this.disabledCheckboxSettings = false;
            }

            this.hideAndShowCheckbox();
        },
        currentIdElementSettings: function() {
            this.hideAndShowCheckbox();
        },
    },
};
</script>
