<template>
    <div>
        <b-row>
            <b-col cols="12" class="statistic-description mb-2">
                <div>
                    Token release period:
                </div>
                <div class="text-xs">
                    Period it will take for the full release of all your tokens not released during creation. Tokens are slowly released over selected time to make sure you won't flood the market.
                    If you choose to release 100% of your tokens immediately this feature will be off and all 10 millions will be accessible by you right now.
                </div>
            </b-col>
            <b-col cols="12">
                <div>Amount released during creation: {{ released }}%</div>
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
            <b-col v-bind:class="{invisible: !showAreaUnlockedTokens}" cols="12">
                <div>Token release period for the rest: {{ releasePeriod }} year(s)</div>
                <b-row class="mx-1 my-2">
                    <b-col cols="2" class="text-center px-0">
                        <font-awesome-icon icon="unlock-alt" class="ml-1 mb-1" />
                    </b-col>
                    <b-col class="p-0">
                        <vue-slider
                            ref="release-period-slider"
                            :disabled="releasePeriodDisabled || !showAreaUnlockedTokens"
                            v-model="releasePeriod"
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
                        class="px-4 mr-1"
                        variant="primary"
                        :disabled="releasePeriodDisabled || loading"
                        @click="saveReleasePeriod"
                    >
                        Save
                    </b-button>
                    <font-awesome-icon v-if="loading" icon="circle-notch" spin class="loading-spinner" fixed-width />
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
import Decimal from 'decimal.js';
import vueSlider from 'vue-slider-component';
import Guide from '../Guide';
import TwoFactorModal from '../modal/TwoFactorModal';
import {NotificationMixin} from '../../mixins';
import {HTTP_OK, HTTP_NO_CONTENT} from '../../utils/constants.js';
import {mapMutations, mapGetters} from 'vuex';

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
            loading: true,
            released: 0,
            releasePeriod: 0,
            showTwoFactorModal: false,
        };
    },
    components: {
        vueSlider,
        Guide,
        TwoFactorModal,
    },
    computed: {
        showAreaUnlockedTokens: function() {
            return 100 !== this.released;
        },
        releasedDisabled: function() {
            return (0 !== this.releasePeriod && this.isTokenExchanged) || !this.isTokenNotDeployed;
        },
        releasePeriodDisabled: function() {
            return !this.isTokenNotDeployed;
        },
        ...mapGetters('tokenStatistics', [
            'getReleasePeriod',
            'getHourlyRate',
            'getReleasedAmount',
            'getFrozenAmount',
        ]),
        tokenReleasePeriod: {
            get() {
                return this.getReleasePeriod;
            },
            set(val) {
                this.setReleasePeriod(val);
            },
        },
        tokenHourlyRate: {
            get() {
                return this.getHourlyRate;
            },
            set(val) {
                this.setHourlyRate(val);
            },
        },
        tokenReleasedAmount: {
            get() {
                return this.getReleaseAmount;
            },
            set(val) {
                this.setReleasedAmount(val);
            },
        },
        tokenFrozenAmount: {
            get() {
                return this.getFrozenAmount;
            },
            set(val) {
                this.setFrozenAmount(val);
            },
        },
    },
    mounted: function() {
        this.$axios.retry.get(this.$routing.generate('lock-period', {
            name: this.tokenName,
        }))
            .then((res) => {
                if (HTTP_OK === res.status) {
                    this.releasePeriod = res.data.releasePeriod;

                    let allTokens = new Decimal(res.data.frozenAmount).add(res.data.releasedAmount);
                    let percent = new Decimal(res.data.releasedAmount).div(allTokens.toString()).mul(100).floor();
                    this.released = percent.toNumber();

                    this.loading = false;
                } else if (HTTP_NO_CONTENT === res.status) {
                    this.releasePeriod = 10;
                    this.released = 10;
                    this.loading = false;
                }
            })
            .catch(() => this.notifyError('Can not load statistic data. Try again later'));
    },
    methods: {
        updateTokenStatistics: function(newTokenStatistics) {
            this.tokenReleasePeriod = newTokenStatistics.releasePeriod;
            this.tokenHourlyRate = newTokenStatistics.hourlyRate;
            this.tokenReleasedAmount = newTokenStatistics.releasedAmount;
            this.tokenFrozenAmount = newTokenStatistics.frozenAmount;
        },
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
                releasePeriod: !this.showAreaUnlockedTokens ? 0 : this.releasePeriod,
                code,
            }).then((response) => {
                this.closeTwoFactorModal();
                this.$emit('update', response);
                this.updateTokenStatistics(response.data);
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
        ...mapMutations('tokenStatistics', [
            'setReleasePeriod',
            'setHourlyRate',
            'setReleasedAmount',
            'setFrozenAmount',
        ]),
    },
};
</script>

<style lang="sass" scoped>
    b
        white-space: nowrap

    .statistic-description
        font-size: 1.2rem
</style>
