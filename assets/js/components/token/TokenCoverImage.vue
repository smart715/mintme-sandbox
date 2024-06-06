<template>
    <div
        class="position-relative d-flex justify-content-center w-100 overflow-hidden cover-image-wrapper"
    >
        <image-uploader
            v-if="editable"
            type="token"
            purpose="cover"
            :token="tokenName"
            @upload="updateCoverImage"
        />
        <img
            v-if="currentImage"
            class="cover-image d-block h-100 w-100"
            :src="currentImage"
        />
    </div>
</template>

<script>
import {mapMutations, mapGetters} from 'vuex';
import ImageUploader from '../ImageUploader';

export default {
    name: 'TokenCoverImage',
    components: {
        ImageUploader,
    },
    props: {
        entryPoint: {
            type: Boolean,
            default: false,
        },
        initImage: {
            type: String,
            default: '',
        },
        editable: {
            type: Boolean,
            default: false,
        },
        tokenName: {
            type: String,
            default: '',
        },
    },
    computed: {
        ...mapGetters('tokenInfo', {
            currentImage: 'getCoverImage',
        }),
    },
    beforeMount() {
        if (this.entryPoint) {
            this.setCoverImage(this.initImage);
        }
    },
    methods: {
        ...mapMutations('tokenInfo', [
            'setCoverImage',
        ]),
        updateCoverImage: function(image) {
            this.setCoverImage(image);
        },
    },
};
</script>
