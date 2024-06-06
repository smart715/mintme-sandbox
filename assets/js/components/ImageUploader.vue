<template>
    <div
        @click="chooseImage"
        class="image-uploader d-flex justify-content-center align-items-center"
        :class="{'c-pointer': !uploading}"
        >
            <div v-if="uploading" class="spinner-border"></div>
            <font-awesome-icon
                v-else
                icon="camera"
                color="#656565"
                size="2x"
                class="absolute-center"
            />
            <input
                type="file"
                class="d-none"
                name="image[file]"
                accept=".jfif,.pjpeg,.jpeg,.pjp,.jpg,.png,.webp"
                ref="fileInput"
                @change="startUpload"
            >
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCamera} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {NotificationMixin} from '../mixins';
import {
    MAX_FILE_BYTES_UPLOAD,
    MIB_BYTES,
} from '../utils/constants';

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
        purpose: {
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
        upload: async function(file) {
            if (file.size > MAX_FILE_BYTES_UPLOAD) {
                this.notifyError(this.$t('page.profile.max_size', this.translationsContext(file.size)));
                this.uploading = false;
                this.$refs.fileInput.value = '';

                return;
            }
            const formData = new FormData();

            formData.append('file', file);
            formData.append('type', this.type);
            formData.append('purpose', this.purpose);
            formData.append('token', this.token);

            this.uploading = true;
            try {
                const response = await this.$axios.single.post(
                    this.$routing.generate('media_upload'),
                    formData,
                    {headers: {'Content-Type': 'multipart/form-data'},
                    });
                this.notifySuccess(this.$t('page.profile.cover_upload'));
                this.$emit('upload', response.data.image);
            } catch (err) {
                this.$refs.fileInput.value = '';
                this.notifyError(err.response?.data?.message || err.message);
            } finally {
                this.uploading = false;
            };
        },
        convertToMib(value) {
            return (value / MIB_BYTES).toFixed(2);
        },
        translationsContext(curruntSize) {
            return {
                maxSize: this.convertToMib(MAX_FILE_BYTES_UPLOAD),
                curruntSize: this.convertToMib(curruntSize),
            };
        },
    },
};
</script>

