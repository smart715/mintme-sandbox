<template>
    <div
        class="d-flex align-items-center font-size-2 text-subtitle c-pointer"
        :class="containerClass"
        @click="$emit('like')"
    >
        <font-awesome-icon
            :icon="['far', 'thumbs-up']"
            class="mr-2"
            transform="up-1.5"
        />
        {{ likes }}
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faThumbsUp} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faThumbsUp);

export default {
    name: 'PostLikes',
    components: {
        FontAwesomeIcon,
    },
    props: {
        isLiked: {
            type: Boolean,
            default: false,
        },
        likes: {
            type: Number,
            default: 0,
        },
    },
    computed: {
        containerClass() {
            return {
                'text-primary': this.isLiked,
            };
        },
    },
};
</script>
