<template>
    <div>
        <modal
            :visible="visible"
            @close="$emit('close')"
            :no-close="true"
            :enable-close-btn="isLastStep"
            dialog-class="token-created-modal-dialog"
        >
            <template v-slot:body>
                <div class="overflow-wrap-break-word">
                    <div
                        v-if="1 === currentStep"
                        class="token-created-step d-block"
                    >
                        <div class="row token-created-step-height">
                            <div class="col-lg-4 col-md-12 align-self-center">
                                <img class="w-100 token-created-step-image mb-4" :src="stepsImages[currentStep-1]" />
                            </div>
                            <div class="col-lg-8 col-md-12 align-self-center">
                                <h1 class="text-bold">
                                    {{ $t('token.intro.created_modal.congratulation') }}
                                </h1>
                                <token-name
                                    :name="tokenName"
                                    class="token-text ml-4"
                                />
                                <p class="mt-3">
                                    {{ $t('token.intro.created_modal.paragraph_1.part_1') }}
                                    <a
                                        :href="tokenLink"
                                        target="_blank"
                                        class="link highlight"
                                    >
                                        {{ tokenLink }}
                                    </a>
                                    <copy-link
                                        :content-to-copy="tokenLink"
                                        class="c-pointer mx-1"
                                    >
                                        <font-awesome-icon :icon="['far', 'copy']"/>
                                    </copy-link>
                                    <span v-html="$t('token.intro.created_modal.paragraph_1.part_2')" />
                                </p>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="2 === currentStep"
                        class="token-created-step d-block"
                    >
                        <div class="row token-created-step-height">
                            <div class="col-lg-4 col-md-12 align-self-center">
                                <img class="w-100 token-created-step-image mb-4" :src="stepsImages[currentStep-1]" />
                            </div>
                            <div class="col-lg-8 col-md-12 align-self-center">
                                <p>
                                    {{ $t('token.intro.created_modal.paragraph_2.part_1') }}
                                </p>
                                <p class="mt-3">
                                    {{ $t('token.intro.created_modal.paragraph_2.part_2') }}
                                    <a
                                        :href="tokenPromotionLink"
                                        target="_blank"
                                        class="link highlight"
                                    >
                                        {{ $t('token.intro.created_modal.paragraph_2.part_2_promotion_tab') }}
                                    </a>
                                    {{ $t('token.intro.created_modal.paragraph_2.part_2_1') }}
                                </p>
                                <p class="mt-3">
                                    {{ $t('token.intro.created_modal.paragraph_2.part_3') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="3 === currentStep"
                        class="token-created-step d-block"
                    >
                        <div class="row token-created-step-height">
                            <div class="col-lg-4 col-md-12 align-self-center">
                                <img class="w-100 token-created-step-image mb-4" :src="stepsImages[currentStep-1]" />
                            </div>
                            <div class="col-lg-8 col-md-12 align-self-center">
                                <p>
                                    {{ $t('token.intro.created_modal.paragraph_3') }}
                                </p>
                                <ul class="token-created-list">
                                    <li>
                                        <a
                                            :href="kbArticles[0]"
                                            target="_blank"
                                            class="link highlight"
                                        >
                                            {{ $t('token.intro.created_modal.list_1.item_1') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            :href="kbArticles[1]"
                                            target="_blank"
                                            class="link highlight"
                                        >
                                            {{ $t('token.intro.created_modal.list_1.item_2') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            :href="kbArticles[2]"
                                            target="_blank"
                                            class="link highlight"
                                        >
                                            {{ $t('token.intro.created_modal.list_1.item_3') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            :href="kbArticles[3]"
                                            target="_blank"
                                            class="link highlight"
                                        >
                                            {{ $t('token.intro.created_modal.list_1.item_4') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            :href="kbArticles[4]"
                                            target="_blank"
                                            class="link highlight"
                                        >
                                            {{ $t('token.intro.created_modal.list_1.item_5') }}
                                        </a>
                                    </li>
                                </ul>
                                <p>
                                    {{ $t('token.intro.created_modal.paragraph_4') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="4 === currentStep"
                        class="token-created-step d-block"
                    >
                        <div class="row token-created-step-height">
                            <div class="col-lg-4 col-md-12 align-self-center">
                                <img class="w-100 token-created-step-image mb-4" :src="stepsImages[currentStep-1]" />
                            </div>
                            <div class="col-lg-8 col-md-12 align-self-center">
                                <p>
                                    {{ $t('token.intro.created_modal.paragraph_5') }}
                                </p>
                                <ol>
                                    <li>
                                        {{ $t('token.intro.created_modal.list_2.item_1') }}
                                    </li>
                                    <li>
                                        {{ $t('token.intro.created_modal.list_2.item_2') }}
                                    </li>
                                </ol>
                                <p>
                                    {{ $t('token.intro.created_modal.paragraph_6.part_1') }}
                                    <a
                                        :href="kbArticles[5]"
                                        target="_blank"
                                        class="link highlight"
                                    >
                                        {{ $t('token.intro.created_modal.paragraph_6.part_2') }}
                                    </a>
                                    {{ $t('token.intro.created_modal.paragraph_6.part_3') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4 justify-content-center">
                        <div
                            class="col-5 col-lg-4 text-left text-lg-left d-lg-block space-prev-btn"
                            :class="prevClasses"
                        >
                            <a
                                :class="{'d-none' : isFirstStep}"
                                class="navigation-btn text-small-responsive"
                                type="button"
                                ref="prevBtn"
                                @click="takeStep(-1)"
                            >
                                <font-awesome-icon :icon="['far', 'arrow-alt-circle-left']" />
                                {{ $t('previous') }}
                            </a>
                        </div>
                        <div
                            class="col-2 col-lg-4 text-lg-center pl-2 pr-2"
                            :class="skipClasses"
                        >
                            <a
                                :class="{'d-none' : isLastStep}"
                                class="navigation-btn text-small-responsive"
                                ref="skipBtn"
                                type="button"
                                @click="$emit('close')"
                            >
                                {{ $t('skip') }}
                            </a>
                        </div>
                        <div
                            class="col-5 col-lg-4 text-right text-lg-right space-next-btn"
                            :class="nextClasses"
                        >
                            <a
                                type="button"
                                :class="{'d-none' : isLastStep}"
                                class="navigation-btn text-small-responsive"
                                ref="nextBtn"
                                @click="takeStep(1)"
                            >
                                {{ $t('next') }}
                                <font-awesome-icon :icon="['far', 'arrow-alt-circle-right']" />
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {
    faCopy,
    faArrowAltCircleRight,
    faArrowAltCircleLeft,
} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Modal from './Modal';
import CopyLink from '../CopyLink';
import {mintmeUrl} from '../../utils/constants';
import TokenName from '../../components/token/TokenName';

library.add(faCopy, faArrowAltCircleRight, faArrowAltCircleLeft);

const IMAGE_OF_STEP1 = require('../../../img/rocket_nv.svg');
const IMAGE_OF_STEP2 = require('../../../img/parachute_nv.svg');
const IMAGE_OF_STEP3 = require('../../../img/blog_nv.svg');
const IMAGE_OF_STEP4 = require('../../../img/arrow_nv.svg');
const kbArticles = [
    `${mintmeUrl}kb/Time-for-token-sales-how-can-I-make-a-difference`,
    `${mintmeUrl}kb/Aiming-at-a-strong-token`,
    `${mintmeUrl}kb/Ideas-to-promote-and-sell-your-token`,
    `${mintmeUrl}kb/Stuck-not-knowing-what-to-do-next`,
    `${mintmeUrl}kb/Talking-to-your-followers-about-MintMe-we-got-some-ideas`,
    `${mintmeUrl}kb/How-to-deploy-a-token-to-the-blockchain`,
];

export default {
    name: 'TokenCreatedModal',
    components: {
        CopyLink,
        Modal,
        FontAwesomeIcon,
        TokenName,
    },
    props: {
        visible: Boolean,
        tokenName: String,
    },
    data() {
        return {
            url: mintmeUrl,
            currentStep: 1,
            stepsImages: [
                IMAGE_OF_STEP1,
                IMAGE_OF_STEP2,
                IMAGE_OF_STEP3,
                IMAGE_OF_STEP4,
            ],
            kbArticles: kbArticles,
        };
    },
    computed: {
        translationsContext: function() {
            return {
                tokenName: this.tokenName,
            };
        },
        tokenLink() {
            return this.$routing.generate('token_show_intro', {name: this.tokenName}, true);
        },
        tokenPromotionLink() {
            return this.$routing.generate(
                'token_settings',
                {
                    tokenName: this.tokenName,
                    tab: 'promotion',
                    sub: 'bounty',
                },
                true
            );
        },
        isFirstStep() {
            return 1 === this.currentStep;
        },
        isLastStep() {
            return this.stepsImages.length === this.currentStep;
        },
        prevClasses() {
            return {'d-none': this.isFirstStep, 'col-12 text-center': this.isLastStep};
        },
        skipClasses() {
            return [this.isFirstStep ? 'col-6 text-right' : 'col-2 text-center'];
        },
        nextClasses() {
            return [this.isFirstStep ? 'col-6': 'col-4'];
        },
    },
    methods: {
        takeStep(newStep) {
            this.currentStep += newStep;
        },
    },
};
</script>
