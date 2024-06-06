<template>
    <div v-if="trendingTags">
        <div
            v-for="tag in tagsToShow"
            :key="tag"
            class="py-3 px-4 font-size-1 c-pointer trending-tag"
            :class="{'active': activeTag === tag}"
            @click="onTagClick(tag)"
        >
            <div class="font-weight-semibold text-truncate">#{{ tag }}</div>
            <div>{{ getTagSubtitle(trendingTags[tag]) }}</div>
        </div>
        <div v-if="!disableShowMore" class="text-center mt-3">
            <button
                class="btn btn-lg button-secondary rounded-pill"
                @click="toggleShowMore"
            >
                <span class="pt-2 pb-2 pl-3 pr-3">
                    {{ showMoreText }}
                </span>
            </button>
        </div>
    </div>
    <div v-else class="d-flex p-3 align-items-center justify-content-center">
        <span class="spinner-border spinner-border-md">
            <span class="sr-only"> {{ $t('loading') }} </span>
        </span>
    </div>
</template>

<script>
import {NotificationMixin} from '../../mixins';

export default {
    name: 'FeedTrendingTags',
    mixins: [NotificationMixin],
    props: {
        activeTag: String,
        amountToShow: {
            type: Number,
            default: 5,
        },
        disableShowMore: Boolean,
    },
    data() {
        return {
            trendingTags: null,
            showMore: false,
        };
    },
    created() {
        this.loadTrendingTags();
    },
    computed: {
        tagsToShow() {
            const tags = Object.keys(this.trendingTags);

            return this.showMore
                ? tags
                : tags.slice(0, this.amountToShow);
        },
        showMoreText() {
            return this.showMore ? this.$t('page.index.see_less') : this.$t('page.index.see_more');
        },
    },
    methods: {
        async loadTrendingTags() {
            try {
                const response = await this.$axios.single.get(this.$routing.generate('trending_tags'));

                this.trendingTags = response.data;
                this.$emit('hashtags-loaded', this.trendingTags);
            } catch (err) {
                this.notifyError(err.response?.data?.message || this.$t('toasted.error.try_reload'));
                this.$logger.error('Could not load trending tags', err);
            }
        },
        onTagClick(tag) {
            this.$emit('hashtag-change', tag);
        },
        getTagSubtitle(amount) {
            return this.$tc(
                'dynamic.popular_tags.posts',
                1 < amount ? 2 : 1,
                {amount},
            );
        },
        toggleShowMore() {
            this.showMore = !this.showMore;
        },
    },
};
</script>
