<template>
    <div>
        <button
            type="button"
            class="btn btn-primary d-flex align-items-center"
            @click="openModal"
        >
            <font-awesome-icon
                icon="play-circle"
                class="h3 m-0"
                fixed-width
            />
            {{ $t('btn.watch_video') }}
        </button>
        <modal
            id="btn-video-modal"
            dialog-class="ytvideo-modal"
            :visible="modalVisible"
            @close="closeModal"
        >
            <template slot="body">
                <iframe
                    id="videoIframe"
                    :src="`https://www.youtube.com/embed/${videoId}?autoplay=1`"
                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </template>
        </modal>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faPlayCircle, faExpand} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Modal from './modal/Modal';

library.add(faPlayCircle, faExpand);

export default {
    name: 'MainPageVideoBtn',
    components: {
        FontAwesomeIcon,
        Modal,
    },
    props: {
        videoId: String,
    },
    data() {
        return {
            modalVisible: false,
        };
    },
    mounted() {
        window.addEventListener('resize', () => {
            this.resizeIframe();
        });
    },
    methods: {
        openModal() {
            this.modalVisible = true;
        },
        closeModal() {
            this.modalVisible = false;
        },
        resizeIframe() {
            const iframe = document.querySelector('#videoIframe');

            if (!iframe) {
                return;
            }

            const verticalRatio = 9 / 16;
            const maxHeight = window.innerHeight * 0.85;
            let width = window.innerWidth * 0.9;
            let height = verticalRatio * width;

            if (verticalRatio * width > maxHeight) {
                height = maxHeight;
                width = height * (16 / 9);
            }

            iframe.setAttribute('width', width.toString());
            iframe.setAttribute('height', height.toString());
        },
    },
    watch: {
        modalVisible() {
            if (!this.modalVisible) {
                return;
            }

            this.$nextTick(() => {
                this.resizeIframe();
            });
        },
    },
};
</script>
