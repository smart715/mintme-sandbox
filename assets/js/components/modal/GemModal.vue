<template>
    <div class="gem-modal-wrp" :class="{'opened': isOpened}">
        <div class="backdrop"></div>
        <div
            class="gem-modal"
            :class="[isClosing ? 'slide-out' : 'slide-in']"
            @click="closeModal"
        >
            <div class="gem-modal-content" @click.stop="">
                <div class="position-relative">
                    <img src="../../../img/gem-modal.png" />
                    <div class="close-btn position-absolute" @click="closeModal">
                        <font-awesome-icon icon="times" />
                    </div>
                </div>
                <div class="description px-3 py-2" v-html="$t('gem_modal_content')"></div>
                <div class="d-flex justify-content-center">
                    <button class="btn btn-primary mt-4 mb-5" @click="readMore">
                        {{ $t('read_more') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import '../../../scss/gem-modal.sass';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faTimes);

export const GEM_MODAL_LS_KEY = 'gem_modal_views';
export const GEM_MODAL_ANIMATION_DURATION_MS = 500;

export default {
    name: 'GemModal',
    components: {
        FontAwesomeIcon,
    },
    props: {
        articleUrl: String,
        appearanceDelay: {
            type: Number,
            default: 0,
        },
        maxViews: {
            type: Number,
            default: 0,
        },
    },
    data() {
        return {
            isOpened: false,
            isClosing: false,
        };
    },
    created() {
        if (this.viewsAmount < this.maxViews) {
            setTimeout(() => this.openModal(), this.appearanceDelay);
        }
    },
    computed: {
        viewsAmount() {
            return parseInt(localStorage.getItem(GEM_MODAL_LS_KEY)) || 0;
        },
    },
    methods: {
        readMore() {
            if (this.articleUrl) {
                window.location.href = this.articleUrl;
                this.saveMaxViews();
            } else {
                this.closeModal();
            }
        },
        openModal() {
            this.increaseViews();
            this.isOpened = true;
        },
        closeModal() {
            this.isClosing = true;

            setTimeout(() => {
                this.isOpened = false;
                this.isClosing = false;
            }, GEM_MODAL_ANIMATION_DURATION_MS);
        },
        increaseViews() {
            localStorage.setItem(GEM_MODAL_LS_KEY, this.viewsAmount ? this.viewsAmount + 1 : 1);
        },
        saveMaxViews() {
            localStorage.setItem(GEM_MODAL_LS_KEY, this.maxViews);
        },
    },
};
</script>
