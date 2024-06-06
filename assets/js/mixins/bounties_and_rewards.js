import {BOUNTY, TOKEN_SHOP, TYPE_BOUNTY} from '../utils/constants';

export default {
    mounted() {
        this.$root.$on('bv::tooltip::show', (BVEvent) => {
            const target = BVEvent.target;

            if (target && target.classList.contains('truncatable')) {
                !this.isItemTruncated(BVEvent.target) ? BVEvent.preventDefault() : null;
            }
        });
    },
    methods: {
        isItemTruncated(itemSpan) {
            const itemParentWidth = itemSpan.parentElement.offsetWidth;
            const itemWidth = itemSpan.offsetWidth;
            const tolerance = 7;
            return itemWidth + tolerance >= itemParentWidth;
        },
        openFinalizeOrSummaryModal(reward, event) {
            if (event && event.preventDefault) {
                event.preventDefault();
            }

            if (!this.isSettingPage && this.isOwner) {
                this.redirectToSettingPageWithSummaryModal(reward);

                return;
            }

            this.$emit(
                this.isSettingPage ? 'on-summary' : 'open-finalize-modal',
                reward,
            );
        },
        redirectToSettingPageWithSummaryModal(reward) {
            window.location.href = this.$routing.generate('token_settings', {
                tokenName: this.tokenName,
                tab: 'promotion',
                modal: 'reward-summary',
                slug: reward.slug,
                sub: TYPE_BOUNTY === reward.type ? BOUNTY : TOKEN_SHOP,
            });
        },
    },
};
