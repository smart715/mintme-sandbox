<template>
    <div class="comment">
        <div>
            <a :href="$routing.generate('profile-view', {nickname: comment.author.profile.nickname})" class="text-white">
                <img
                    :src="comment.author.profile.image.avatar_small"
                    class="rounded-circle d-inline-block"
                    alt="avatar"
                >
                {{ comment.author.profile.nickname }}
            </a>
            <span class="comment-date">
                {{ date }}
            </span>
            <template v-if="comment.editable">
                <button class="btn btn-link p-0 delete-icon float-right text-decoration-none text-reset">
                    <font-awesome-icon
                        class="icon-default c-pointer align-middle"
                        icon="trash"
                        transform="shrink-4 up-1.5"
                    />
                </button>
                <a class="btn btn-link p-0 post-edit-icon float-right text-decoration-none text-reset" href="#">
                    <font-awesome-icon
                        class="icon-default c-pointer align-middle"
                        icon="edit"
                        transform="shrink-4 up-1.5"
                    />
                </a>
            </template>
        </div>
        <p>
            {{ comment.content }}
        </p>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faTrash} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import moment from 'moment';

library.add(faEdit);
library.add(faTrash);

export default {
    name: 'Comment',
    components: {
        FontAwesomeIcon,
    },
    props: {
        comment: Object,
    },
    data() {
        return {
        };
    },
    computed: {
        date() {
            return moment(this.comment.createdAt).format('H:mm, MMM D, YYYY');
        },
    },
    methods: {
    },
};
</script>
