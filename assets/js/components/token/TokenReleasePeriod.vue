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
                    because unlocking all tokens take time.
                </div>
            </b-col>
            <b-col cols="12">
                <div>Amount released at beginning: {{ released }}%</div>
                <b-row class="mx-1 my-2">
                    <b-col cols="2" class="text-center px-0">
                        <b>0%</b>
                    </b-col>
                    <b-col class="p-0">
                        <vue-slider
                            ref="released-slider"
                            :disabled="releasedDisabled"
                            v-model="released"
                            :min="0" :max="100"
                            :interval="1"
                            :tooltip="false"
                            width="100%"
                        />
                    </b-col>
                    <b-col cols="2" class="text-center px-0">
                        <b>100%</b>
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
                            :disabled="currentPeriodDisabled"
                            v-model="currentPeriod"
                            :data="[1,2,3,5,10,15,20,30,40,50]"
                            :interval="10"
                            :tooltip="false"
                            width="100%"
                        />
                    </b-col>
                    <b-col cols="2" class="text-center px-0">
                        <font-awesome-icon icon="lock" class="ml-1 mb-1" />
                    </b-col>
                </b-row>
            </b-col>
            <b-col cols="12" class="mt-3">
                <div class="text-left">
                    <b-button
                        type="submit"
                        class="px-4"
                        variant="primary"
                        :disabled="currentPeriodDisabled"
                        @click="saveReleasePeriod"
                    >
                        Save
                    </b-button>

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
import Guide from '../Guide';
import TwoFactorModal from '../modal/TwoFactorModal';
import {NotificationMixin} from '../../mixins';

const DEFAULT_VALUE = '-';

export default {
    name: 'TokenReleasePeriod',
    mixins: [NotificationMixin],
    props: {
        isTokenExchanged: Boolean,
        isTokenNotDeployed: Boolean,
        tokenName: String,
        twofa: Boolean,
    },
    data() {
        return {
            currentPeriod: this.period,
            released: 10,
            releasePeriod: DEFAULT_VALUE,
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
            return (this.releasePeriod !== DEFAULT_VALUE && this.isTokenExchanged) || !this.isTokenNotDeployed;
        },
        currentPeriodDisabled: function() {
            return !this.isTokenNotDeployed;
        },
        period: function() {
            return this.releasedDisabled ? this.releasePeriod : 10;
        },
    },
    mounted: function() {
        this.$axios.retry.get(this.$routing.generate('lock-period', {
            name: this.tokenName,
        }))
            .then((res) => this.releasePeriod = res.data.releasePeriod || this.releasePeriod)
            .catch(() => this.notifyError('Can not load statistic data. Try again later'));
    },
    methods: {
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
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
                released: this.released,
                releasePeriod: this.currentPeriod,
                code,
            }).then((response) => {
                this.closeTwoFactorModal();
                this.$emit('update', response);
                this.notifySuccess('Release period updated.');
            }).catch(({response}) => {
                if (!response) {
                    this.notifyError('Network error');
                } else if (response.data.message) {
                    this.notifyError(response.data.message);
                } else {
                    this.notifyError('An error has occurred, please try again later');
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
