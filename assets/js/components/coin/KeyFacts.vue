<template>
    <div class="key-facts w-100 py-5 px-2">
        <img src="../../../img/main_dots.webp" class="key-facts-dots first-dots" />
        <img src="../../../img/main_dots.webp" class="key-facts-dots second-dots" />
        <img src="../../../img/main_dots.webp" class="key-facts-dots third-dots" />
        <div class="key-facts-content mx-auto">
            <h2
                class="section-header key-facts-header text-center mb-4"
                v-html="$t('page.coin.key_facts.header')"
            ></h2>
            <div class="body container py-5">
                <div class="row mx-0 justify-content-around">
                    <div
                        v-for="(keyFactAsset, keyFactNumber) in keyFactsAssets"
                        :key="keyFactNumber"
                        :data-aos="getKeyFactAnimation(keyFactNumber)"
                        class="col-12 col-xl-4 p-3 key-fact text-center"
                    >
                        <img
                            :src="keyFactAsset"
                            :alt="'mintme-coin-illustration-' + (keyFactNumber + 1)"
                            loading="lazy"
                        />
                        <h4 class="key-fact-header pb-4 mb-0">
                            <span>
                                {{ keyFactsTrans.header[keyFactNumber] }}
                            </span>
                        </h4>
                        <div class="key-fact-text text-left mx-auto">
                            <span>
                                {{ keyFactsTrans.text[keyFactNumber] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import asset1 from '../../../img/mintme-coin-illustration-1.svg';
import asset2 from '../../../img/mintme-coin-illustration-2.svg';
import asset3 from '../../../img/mintme-coin-illustration-3.svg';
import asset4 from '../../../img/mintme-coin-illustration-4.svg';
import asset5 from '../../../img/mintme-coin-illustration-5.svg';
import asset6 from '../../../img/mintme-coin-illustration-6.svg';
import asset7 from '../../../img/mintme-coin-illustration-7.svg';
import asset8 from '../../../img/mintme-coin-illustration-8.png';
import asset9 from '../../../img/mintme-coin-illustration-9.png';

const animations = {
    slideRight: 'slide-right',
    slideLeft: 'slide-left',
    zoomIn: 'zoom-in',
};

export default {
    name: 'KeyFacts',
    data() {
        return {
            keyFactsAssets: [
                asset1,
                asset2,
                asset3,
                asset4,
                asset5,
                asset6,
                asset7,
                asset8,
                asset9,
            ],
            keyFactsTrans: {
                header: [
                    this.$t('page.coin.key_fact.header.1'),
                    this.$t('page.coin.key_fact.header.2'),
                    this.$t('page.coin.key_fact.header.3'),
                    this.$t('page.coin.key_fact.header.4'),
                    this.$t('page.coin.key_fact.header.5'),
                    this.$t('page.coin.key_fact.header.6'),
                    this.$t('page.coin.key_fact.header.7'),
                    this.$t('page.coin.key_fact.header.8'),
                    this.$t('page.coin.key_fact.header.9'),
                ],
                text: [
                    this.$t('page.coin.key_fact.body.1'),
                    this.$t('page.coin.key_fact.body.2'),
                    this.$t('page.coin.key_fact.body.3'),
                    this.$t('page.coin.key_fact.body.4'),
                    this.$t('page.coin.key_fact.body.5'),
                    this.$t('page.coin.key_fact.body.6'),
                    this.$t('page.coin.key_fact.body.7'),
                    this.$t('page.coin.key_fact.body.8'),
                    this.$t('page.coin.key_fact.body.9'),
                ],
            },
        };
    },
    methods: {
        getKeyFactAnimation: function(keyFactNumber) {
            if (0 === keyFactNumber % 3) {
                return animations.slideRight;
            }
            if (1 === keyFactNumber % 3) {
                return animations.zoomIn;
            }
            if (2 === keyFactNumber % 3) {
                return animations.slideLeft;
            }
        },
    },
};
</script>
