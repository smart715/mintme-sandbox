<template>
    <div
        class="avatar overflow-hidden"
        :class="classObject"
        :tabindex="isTabIndex"
    >
        <ImageUploader
            v-if="editable"
            :type="type"
            purpose="avatar"
            :token="token"
            @upload="setImage"
        />
        <img :src="imageUrl" class="avatar-img rounded-circle img-fluid" :class="imgClass">
    </div>
</template>

<script>
import ImageUploader from './ImageUploader';

export default {
    name: 'Avatar',
    components: {
        ImageUploader,
    },
    props: {
        token: String,
        image: {
            type: String,
        },
        editable: {
            type: Boolean,
            default: false,
        },
        type: {
            type: String,
            default: 'profile',
        },
        size: {
            type: String,
            default: 'small',
        },
        fallback: {
            type: String,
            default: '',
        },
        imgClass: {
            type: String,
            default: '',
        },
    },
    data() {
        return {
            currentImage: this.image,
        };
    },
    computed: {
        classObject: function() {
            return this.token
                ? {}
                : {
                    [`avatar__${this.size}`]: true,
                };
        },
        imageUrl: function() {
            return this.currentImage ? this.currentImage : this.fallback;
        },
        isTabIndex: function() {
            return this.editable ? 0 : -1;
        },
    },
    methods: {
        setImage(image) {
            this.currentImage = image;
        },
    },
};
</script>
