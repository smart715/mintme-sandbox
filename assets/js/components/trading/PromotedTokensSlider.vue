<template>
    <div class="col-12 m-0 px-3 px-xl-0 promoted-tokens-slider-wrp d-flex">
        <div class="slider-arrows">
            <button class="slider-arrow arrow-left" :class="{'d-none': !showLeftArrow}" @click="moveLeft">
                <font-awesome-icon icon="caret-left" />
            </button>
            <button class="slider-arrow arrow-right" :class="{'d-none': !showRightArrow}" @click="moveRight">
                <font-awesome-icon icon="caret-right" />
            </button>
        </div>
        <div class="slides-list-wrp overflow-hidden">
            <div class="d-inline-flex slides-list">
                <div v-for="(token, index) in randomizedTokens" :key="index" class="slide">
                    <a :href="getTokenUrl(token.name)" class="d-flex flex-column slide-link">
                        <coin-avatar
                            is-user-token
                            image-class="coin-avatar-xl"
                            class="mx-3"
                            :image="token.image.avatar_large"
                        />
                        <div class="text-center mt-1 text-truncate">{{ token.name }}</div>
                        <div class="text-center text-truncate">{{ usdSign }}{{ token.price }}</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import CoinAvatar from '../CoinAvatar';
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {
    faCaretLeft,
    faCaretRight,
} from '@fortawesome/free-solid-svg-icons';
import throttle from 'lodash/throttle';
import {usdSign} from '../../utils/constants';

library.add(faCaretLeft, faCaretRight);

export default {
    name: 'PromotedTokensSlider',
    components: {
        CoinAvatar,
        FontAwesomeIcon,
    },
    props: {
        promotions: Array,
    },
    data() {
        return {
            randomizedTokens: [],
            handleResizeThrottled: null,
            currentSlideIndex: 0,
            usdSign: usdSign,
            slideWidth: 120,
            maxSlideIndex: 0,
            moveSlideInstance: null,
            compensate: 0,
            compensated: false,
            carouselSpeed: 4000,
        };
    },
    beforeMount() {
        this.randomizedTokens = this.shuffleArray(this.promotions.map((p) => p.token));

        this.handleResizeThrottled = throttle(() => this.initSlider(), 500);
    },
    mounted() {
        window.addEventListener('resize', this.handleResizeThrottled);
        this.initSlider();
    },
    destroyed() {
        window.removeEventListener('resize', this.handleResizeThrottled);
    },
    computed: {
        showLeftArrow() {
            return 0 !== this.currentSlideIndex;
        },
        showRightArrow() {
            return this.currentSlideIndex !== this.maxSlideIndex || (0 !== this.compensate && !this.compensated);
        },
    },
    methods: {
        initSlider() {
            // try until slider is rendered
            if (!document.querySelector('.slides-list') || 0 === document.querySelector('.slides-list').offsetWidth) {
                setTimeout(() => this.initSlider(), 1000);

                return;
            }

            this.currentSlideIndex = 0;
            this.maxSlideIndex = 0;
            this.compensate = 0;

            const wrpWidth = document.querySelector('.slides-list-wrp').offsetWidth;
            const slidesAmount = this.randomizedTokens.length;

            this.setTranslateX(0);
            this.autoplay();

            if (document.querySelector('.slides-list').offsetWidth <= wrpWidth) {
                return;
            }

            const slidesPerView = Math.round(wrpWidth / this.slideWidth);
            this.maxSlideIndex = slidesAmount - slidesPerView;

            if (slidesPerView * this.slideWidth === wrpWidth) {
                this.compensate = 0;
            } else {
                this.compensate = slidesPerView * this.slideWidth - wrpWidth;
            }
        },
        autoplay() {
            if (this.moveSlideInstance) {
                clearTimeout(this.moveSlideInstance);
            }

            this.moveSlideInstance = setTimeout(() => this.moveRight(), this.carouselSpeed);
        },
        moveRight() {
            this.autoplay();

            if (this.currentSlideIndex < this.maxSlideIndex) {
                this.currentSlideIndex++;
                this.setTranslateX(-this.currentSlideIndex * this.slideWidth);

                return;
            }

            if (this.currentSlideIndex === this.maxSlideIndex && 0 !== this.compensate && !this.compensated) {
                this.setTranslateX(-this.currentSlideIndex * this.slideWidth - this.compensate);
                this.compensated = true;
            }
        },
        moveLeft() {
            this.autoplay();

            if (this.currentSlideIndex === this.maxSlideIndex && 0 !== this.compensate && this.compensated) {
                this.setTranslateX(-this.currentSlideIndex * this.slideWidth);
                this.compensated = false;

                return;
            }

            if (0 === this.currentSlideIndex) {
                return;
            }

            this.currentSlideIndex--;
            this.setTranslateX(-this.currentSlideIndex * this.slideWidth);
        },
        setTranslateX(distance) {
            document.querySelector('.slides-list').style.transform = `translateX(${distance}px)`;
        },
        shuffleArray(array) {
            for (let i = array.length - 1; 0 < i; i--) {
                const j = Math.floor(Math.random() * (i + 1));

                [array[i], array[j]] = [array[j], array[i]];
            }

            return array;
        },
        getTokenUrl(name) {
            return this.$routing.generate('token_show_intro', {name});
        },
    },
};
</script>
