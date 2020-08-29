<template>
    <div class="post" :id="post.id">
        <div>
            <a :href="$routing.generate('profile-view', {nickname: post.author.nickname})" class="text-white">
                <img
                    :src="post.author.image.avatar_small"
                    class="rounded-circle d-inline-block"
                    alt="avatar"
                >
                {{ post.author.nickname }}
            </a>
            <span class="post-date">
                {{ date }}
            </span>
            <copy-link :content-to-copy="link" class="c-pointer ml-1">
              <font-awesome-icon :icon="['far', 'copy']"/>
            </copy-link>
        </div>
        <template v-if="loggedIn">
            <p v-if="post.content">
                <bbcode-view :value="post.content"/>
            </p>
            <p v-else>
              To see this post you need to have <a href="#" @click.prevent="$emit('go-to-trade', post.amount)">{{post.amount | toMoney | formatMoney}} {{post.token.name}}</a> in your balance. Visit trade page and create buy order to get required tokens.
            </p>
        </template>
        <p v-else>
            To see this post you need to <a :href="$routing.generate('login')">log in</a> or <a :href="$routing.generate('register')">sign up</a>.
        </p>
        <a :href="$routing.generate('show_post', {id: post.id})" class="hover-icon text-decoration-none text-white">
            <font-awesome-icon
                class="c-pointer align-middle"
                icon="comment"
                transform="shrink-4 up-1.5"
            />
          <span class="social-link">{{ post.commentsCount }} Comments</span>
        </a>
        <button v-if="showEdit"
            class="btn btn-link p-0 delete-icon float-right text-decoration-none text-reset"
            :disabled="deleteDisabled"
            @click="showModal"
        >
            <font-awesome-icon
                class="icon-default c-pointer align-middle"
                icon="trash"
                transform="shrink-4 up-1.5"
            />
        </button>
        <a v-if="showEdit"
            class="btn btn-link p-0 post-edit-icon float-right text-decoration-none text-reset"
            :href="$routing.generate('edit_post_page', {id: post.id})"
        >
            <font-awesome-icon
                class="icon-default c-pointer align-middle"
                icon="edit"
                transform="shrink-4 up-1.5"
            />
        </a>
        <confirm-modal
            :visible="isModalVisible"
            @confirm="deletePost"
            @close="closeModal"
        >
            <p class="text-white modal-title pt-2">
                Do you really want to delete this post?
            </p>
        </confirm-modal>
    </div>
</template>

<script>
import BbcodeView from '../bbcode/BbcodeView';
import moment from 'moment';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faTrash, faComment} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {MoneyFilterMixin, NotificationMixin} from '../../mixins';
import ConfirmModal from '../modal/ConfirmModal';
import CopyLink from '../CopyLink';

library.add(faEdit);
library.add(faTrash);
library.add(faComment);

export default {
    name: 'Post',
    mixins: [
        MoneyFilterMixin,
        NotificationMixin,
    ],
    components: {
        BbcodeView,
        ConfirmModal,
        FontAwesomeIcon,
        CopyLink,
    },
    props: {
        post: Object,
        index: {
            type: Number,
            default: null,
        },
        showEdit: {
            type: Boolean,
            default: false,
        },
        loggedIn: Boolean,
    },
    data() {
        return {
            deleteDisabled: false,
            isModalVisible: false,
        };
    },
    computed: {
        date() {
            return moment(this.post.createdAt).format('H:mm, MMM D, YYYY');
        },
        link() {
            return this.$routing.generate('token_show', {name: this.post.token.name, tab: 'posts'}, true) + '#' + this.post.id;
        },
    },
    methods: {
        deletePost() {
            this.deleteDisabled = true;
            this.$axios.single.post(this.$routing.generate('delete_post', {id: this.post.id}))
            .then((res) => {
               this.$emit('delete-post', this.index);
               this.notifySuccess(res.data.message);
               this.deleteDisabled = false;
            })
            .catch(() => {
                this.notifyError('Error deleting post.');
            });
        },
        showModal() {
            this.isModalVisible = true;
        },
        closeModal() {
            this.isModalVisible = false;
        },
    },
};
</script>
