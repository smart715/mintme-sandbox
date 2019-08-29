<template>
<div>
    <b-row>
        <b-col cols="12" class="statistic-description mb-2">
            <div>
                Token release period:
            </div>
            <div class="text-xs">
                Period it will take for the full release of your newly created token,
                something similar to escrow. Mintme acts as 3rd party that ensure you won’t
                flood market with all of your tokens which could lower price significantly,
                because unlocking all tokens take time. It’s released hourly
            </div>
        </b-col>
        <b-col cols="12">
            <div>Amount released at beginning: {{ released }}%</div>
            <b-row class="mx-1 my-2">
                <b-col cols="2" class="text-center px-0">
                    <b>1%</b>
                </b-col>
                <b-col class="p-0">
                    <vue-slider
                        ref="released-slider"
                        :disabled="releasedDisabled"
                        v-model="released"
                        :min="1" :max="99"
                        :interval="1"
                        :tooltip="false"
                        width="100%">
                    </vue-slider>
                </b-col>
                <b-col cols="2" class="text-center px-0">
                    <b>99%</b>
                </b-col>
            </b-row>
        </b-col>
        <b-col cols="12">
            <div>Time needed to unlock all tokens: {{ currentPeriod }} years</div>
            <b-row class="mx-1 my-2">
                <b-col cols="2" class="text-center px-0">
                    <font-awesome-icon icon="unlock-alt" class="ml-1 mb-1" />
                </b-col>
                <b-col class="p-0">
                    <vue-slider
                        ref="release-period-slider"
                        v-model="currentPeriod"
                        :data="[1,2,3,5,10,15,20,30,40,50]"
                        :interval="10"
                        :tooltip="false"
                        width="100%">
                    </vue-slider>
                </b-col>
                <b-col cols="2" class="text-center px-0">
                    <font-awesome-icon icon="lock" class="ml-1 mb-1" />
                </b-col>
            </b-row>
        </b-col>
        <b-col cols="12" class="mt-3">
            <div class="text-left">
                <b-button type="submit" class="px-4" variant="primary" @click="saveReleasePeriod">Save</b-button>
                <span class="btn-cancel pl-3 c-pointer" @click="cancelAction">Cancel</span>
            </div>
        </b-col>
    </b-row>
    <two-factor-modal
        :visible="showTwoFactorModal"
        :twofa="twofa"
        @verify="doSaveReleasePeriod"
        @close="closeTwoFactorModal"
    />
    </div>
</template>

<script>
import vueSlider from 'vue-slider-component';
import {deepFlatten} from '../../../utils';
import Guide from '../../Guide';
import TwoFactorModal from '../../modal/TwoFactorModal';

const defaultValue = '-';

export default {
    name: 'TokenIntroductionReleasePeriod',
    props: {
        tokenName: String,
        twofa: Boolean,
    },
    data() {
        return {
            released: 1,
            currentPeriod: this.period,
            releasePeriod: defaultValue,
            showTwoFactorModal: false,
        };
    },
    components: {
        vueSlider,
        Guide,
        TwoFactorModal,
    },
    computed: {
        releasedDisabled: function() {
            return this.releasePeriod !== defaultValue && this.isTokenExchanged;
        },
        period: function() {
            return !this.releasedDisabled ? 10 : this.releasePeriod;
        },
    },
    mounted: function() {
        this.$axios.retry.get(this.$routing.generate('lock-period', {
            name: this.tokenName,
        }))
            .then((res) => this.releasePeriod = res.data.releasePeriod || this.releasePeriod)
            .catch(() => this.$toasted.error('Can not load statistic data. Try again later'));
    },
    methods: {
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
        cancelAction: function() {
            this.$emit('cancel');
        },
        saveReleasePeriod: function() {
            if (!this.twofa) {
                return this.doSaveReleasePeriod();
            }

            return this.showTwoFactorModal = true;
        },
        doSaveReleasePeriod: function(code = '') {
            this.$axios.single.post(this.$routing.generate('lock_in', {
                name: this.tokenName,
            }), {
                'released': this.released,
                'releasePeriod': this.currentPeriod,
                'code': code,
            }).then((response) => {
                this.$emit('onStatsUpdate', response);
                this.$toasted.success('Release period updated.');
                this.cancelAction();
            }).catch((error) => {
                if (400 === error.response.status) {
                    deepFlatten(error.response.data.errors).forEach((err) => {
                        this.$toasted.error(err);
                    });
                } else if (401 === error.response.status) {
                    this.$toasted.error(error.response.data);
                } else {
                    this.$toasted.error('Connection problem. Try again later.');
                }
            });
        },
    },
};
</script>

<style lang="sass" scoped>
    b
        white-space: nowrap

    .statistic-description
        font-size: 1.2rem
</style>
