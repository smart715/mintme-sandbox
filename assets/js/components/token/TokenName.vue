<template>
    <div class="text-truncate">
        <h1
            class="h2 text-white text-truncate"
            v-b-tooltip="tooltipTokenName(tokenName)"
        >
            {{ tokenName | truncate(maxLengthToTruncate) }}
        </h1>
    </div>
</template>

<script>
import {VBTooltip} from 'bootstrap-vue';
import {
    TokenPageTabSelector,
    FiltersMixin,
} from '../../mixins/';
import {TOKEN_NAME_TRUNCATE_LENGTH} from '../../utils/constants';

export default {
    name: 'TokenName',
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        TokenPageTabSelector,
        FiltersMixin,
    ],
    props: {
        tabIndex: Number,
        name: String,
    },
    data() {
        return {
            currentName: this.name,
            maxLengthToTruncate: TOKEN_NAME_TRUNCATE_LENGTH,
        };
    },
    computed: {
        tokenName: function() {
            return this.checkTabName();
        },
    },
    watch: {
        tabIndex(val, oldVal) {
            this.setPageTitle();
        },
    },
    methods: {
        tooltipTokenName: function(tokenName) {
            return this.checkLengthName(tokenName)
                ? {title: this.currentName, boundary: 'viewport'}
                : '';
        },
        checkLengthName: function(tokenName) {
            return tokenName.length > this.maxLengthToTruncate;
        },
        checkTabName: function() {
            const translationContext = {name: this.currentName};

            if (this.isTradeTab) {
                return this.$t('token.trade.token_name', translationContext);
            }

            if (this.isVotingTab) {
                return this.$t('token.voting.token_name', translationContext);
            }

            if (this.isPostTab) {
                return this.$t('token.posts.token_name', translationContext);
            }

            return this.currentName;
        },
        setPageTitle() {
            const translationContext = {name: this.name};
            let title = '';

            if (this.isIntroTab) {
                title = this.$t('page.pair.title_info', translationContext);
            } else if (this.isVotingTab) {
                title = this.$t('page.pair.title_voting', translationContext);
            } else if (this.isPostTab) {
                title = this.$t('page.pair.title_posts', translationContext);
            } else if (this.isTradeTab) {
                title = this.$t('page.pair.title_market_tab', translationContext);
            }

            return document.title = title;
        },
    },
};
</script>
