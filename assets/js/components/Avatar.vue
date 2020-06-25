<template>
    <div class="avatar" :class="classObject" @click="upload">
        <img :src="imageUrl"
             class="rounded-circle img-fluid"
        >
        <ImageUploader
                ref="uploader"
                v-if="editable"
                :type="type"
                @upload="setImage"
        />
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
        },
        data() {
            return {
                currentImage: this.image,
            };
        },
        computed: {
            classObject: function() {
                return {
                    [`avatar__${this.size}`]: true,
                    'c-pointer': this.editable,
                };
            },
            imageUrl: function() {
                return this.currentImage ? this.currentImage : this.fallback;
            },
        },
        methods: {
            setImage(image) {
                this.currentImage = image;
            },
            upload() {
                if (!this.editable) {
                    return;
                }

                this.$refs.uploader.chooseImage();
            },
        },
    };
</script>
