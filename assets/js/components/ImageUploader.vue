<template>
    <div>
        <div
            class="avatar-overlay"
            v-if="uploading"
        >
            <div class="spinner-border"></div>
        </div>
        <font-awesome-icon
            v-if="!uploading"
            icon="camera"
            color="#656565"
            size="2x"
            class="avatar-overlay icon-camera"></font-awesome-icon
        >
        <input type="file"
           name="image[file]"
           accept=".jfif,.pjpeg,.jpeg,.pjp,.jpg,.png,.webp"
           ref="fileInput" style="display: none;" @change="startUpload"
        >
    </div>
</template>

<script>
    import {library} from '@fortawesome/fontawesome-svg-core';
    import {faCamera} from '@fortawesome/free-solid-svg-icons';
    import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
    import {NotificationMixin} from '../mixins';

    library.add(faCamera);

    export default {
        name: 'ImageUploader',
        components: {
            FontAwesomeIcon,
        },
        mixins: [NotificationMixin],
        props: {
            type: {
                type: String,
                default: '',
            },
            token: {
                type: String,
                default: null,
            },
        },
        data() {
            return {
                uploading: false,
            };
        },
        methods: {
            chooseImage: function() {
                if (this.uploading) {
                    return;
                }

                this.$refs.fileInput.click();
            },
            startUpload: function() {
                if (!this.$refs.fileInput.files) {
                    return;
                }

                this.upload(this.$refs.fileInput.files[0]);
            },
            upload: function(file) {
                let formData = new FormData();

                formData.append('file', file);
                formData.append('type', this.type);
                formData.append('token', this.token);

                this.uploading = true;

                this.$axios.single.post(this.$routing.generate('media_upload'), formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                })
                    .then(({data}) => {
                        this.$emit('upload', data.image);
                    })
                    .catch((error) => {
                        this.$refs.fileInput.value = '';
                        this.notifyError(error.response.data.message || error.message);
                    })
                    .finally(() => {
                        this.uploading = false;
                    });
            },
        },
    };
</script>

