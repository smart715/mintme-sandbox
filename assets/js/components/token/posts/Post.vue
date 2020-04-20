<template>
    <div class="post">
        <p v-if="post.content">
            {{ post.content }}
        </p>
        <p v-else>
            To see this post you need to have {{post.amount | toMoney | formatMoney}} {{post.token.name}} in your balance. Visit trade page and create buy order to get required tokens.
        </p>
        <span><u>{{ date }}</u></span>
        <a :href="$routing.generate('profile-view', {pageUrl: post.author.page_url})">{{ author }}</a>
    </div>
</template>

<script>
import moment from 'moment';
import {MoneyFilterMixin} from '../../../mixins';

export default {
    name: 'Post',
    mixins: [
        MoneyFilterMixin,
    ],
    props: {
        post: Object,
    },
    computed: {
        author() {
            return `${this.post.author.firstName} ${this.post.author.lastName}`;
        },
        date() {
            return moment(this.post.createdAt).format('H:mm, MMM D, YYYY');
        }
    }
};
</script>
