<template>
    <div class="post">
        <p v-if="post.content">
            <bbcode-view :value="post.content" />
        </p>
        <p v-else>
            To see this post you need to have {{post.amount | toMoney | formatMoney}} {{post.token.name}} in your balance. Visit trade page and create buy order to get required tokens.
        </p>
        <span>
            {{ date }}
        </span>
        <a :href="$routing.generate('profile-view', {pageUrl: post.author.page_url})">
            {{ author }}
        </a>
        <a v-if="post.editable"
            class="float-right"
            :href="$routing.generate('edit_post_page', {id: post.id})"
        >
            Edit
        </a>
        <a v-if="post.editable"
            class="float-right"
            href="#"
            @click.prevent="deletePost"
        >
            Delete
        </a>
    </div>
</template>

<script>
import BbcodeView from '../bbcode/BbcodeView';
import moment from 'moment';
import {MoneyFilterMixin, NotificationMixin} from '../../mixins';

export default {
    name: 'Post',
    mixins: [
        MoneyFilterMixin,
        NotificationMixin,
    ],
    components: {
        BbcodeView,
    },
    props: {
        post: Object,
        key: {
            type: Number,
            default: null,
        },
    },
    computed: {
        author() {
            return `${this.post.author.firstName} ${this.post.author.lastName}`;
        },
        date() {
            return moment(this.post.createdAt).format('H:mm, MMM D, YYYY');
        },
    },
    methods: {
        deletePost() {
            this.$axios.single.post('delete_post', {id: post.id})
            .then(() => {
               this.$emit('delete-post', this.key);
               this.notifySuccess('Post deleted');
            })
            .catch(() => {
                this.notifyError('Error deleting post.');
            });
        },
    },
};
</script>
