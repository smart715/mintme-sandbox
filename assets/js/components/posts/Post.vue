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
            <a :href="singlePageUrl" class="text-decoration-none post-date">
                {{ date }}
            </a>
            <copy-link :content-to-copy="link" class="c-pointer ml-1">
              <font-awesome-icon :icon="['far', 'copy']"/>
            </copy-link>
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
        </div>
        <template>
            <p v-if="post.content">
                <bbcode-view :value="post.content"/>
            </p>
            <p v-else>
              {{ $t('post.logged_in.1') }} <a href="#" @click.prevent="$emit('go-to-trade', post.amount)">{{post.amount | toMoney | formatMoney}} {{post.token.name}}</a> {{ $t('post.logged_in.2') }}
            </p>
        </template>
        <a :href="singlePageUrl" class="hover-icon text-decoration-none text-white">
            <font-awesome-icon
                class="c-pointer align-middle"
                icon="comment"
                transform="grow-1.5"
            />
          <span class="social-link ml-1">{{ post.commentsCount }} {{ $t('post.comments') }}</span>
        </a>
        <confirm-modal
            :visible="isModalVisible"
            @confirm="deletePost"
            @close="closeModal"
        >
            <p class="text-white modal-title pt-2">
                {{ $t('post.delete') }}
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
        singlePageUrl() {
            return this.$routing.generate('show_post', {id: this.post.id});
        },
    },
    methods: {
        deletePost() {
            this.deleteDisabled = true;
            this.$axios.single.post(this.$routing.generate('delete_post', {id: this.post.id}))
            .then((res) => {
               this.$emit('delete-post', this.index);
               this.notifySuccess(this.$t('post.deleted'));
            })
            .catch(() => {
                this.notifyError(this.$t('post.error.deleted'));
            })
            .finally(() => {
                this.deleteDisabled = false;
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
