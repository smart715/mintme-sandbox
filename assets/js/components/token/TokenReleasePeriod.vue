<template>
    <div>
        <b-row>
            <b-col cols="12" class="statistic-description mb-2">
                <div>
                    {{ $t('token.release_period.header') }}
                </div>
                <div class="text-xs">
                    {{ $t('token.release_period.body') }}
                </div>
            </b-col>
            <b-col cols="12">
                <div>{{ $t('token.release_period.amount', {released: released}) }}</div>
                <b-row class="mx-1 my-2">
                    <b-col cols="2" class="text-center px-0">
                        <b>20%</b>
                    </b-col>
                    <b-col class="p-0">
                        <vue-slider
                            ref="released-slider"
                            :disabled="releasedDisabled"
                            v-model="released"
                            :min="20" :max="100"
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
                <div>{{ $t('token.release_period.for_rest_years', {releasePeriod: releasePeriod}) }}</div>
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
                        {{ $t('save') }}
                    </b-button>
                    <font-awesome-icon v-if="loading" icon="circle-notch" spin class="loading-spinner" fixed-width />
                </div>
            </b-col>
        </b-row>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faLock, faUnlockAlt} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Decimal from 'decimal.js';
import vueSlider from 'vue-slider-component';
import {BRow, BCol, BButton} from 'bootstrap-vue';
import {LoggerMixin, NotificationMixin} from '../../mixins';
import {HTTP_OK, HTTP_NO_CONTENT} from '../../utils/constants.js';
import {mapMutations} from 'vuex';

library.add(faCircleNotch, faLock, faUnlockAlt);

export default {
    name: 'TokenReleasePeriod',
    components: {
        BRow,
        BCol,
        BButton,
        vueSlider,
        FontAwesomeIcon,
    },
    mixins: [
        NotificationMixin,
        LoggerMixin,
    ],
    props: {
        isTokenExchanged: Boolean,
        isTokenNotDeployed: Boolean,
        tokenName: String,
    },
    data() {
        return {
            loading: true,
            released: 20,
            releasePeriod: 0,
            hasLockin: false,
        };
    },
    computed: {
        showAreaUnlockedTokens: function() {
            return 100 !== this.released;
        },
        releasedDisabled: function() {
            return (this.hasLockin && 0 !== this.releasePeriod && this.isTokenExchanged) || !this.isTokenNotDeployed;
        },
        releasePeriodDisabled: function() {
            return !this.isTokenNotDeployed;
        },
    },
    mounted: function() {
        this.$axios.retry.get(this.$routing.generate('lock-period', {
            name: this.tokenName,
        }))
            .then((res) => {
                if (HTTP_OK === res.status) {
                    this.hasLockin = true;
                    this.releasePeriod = res.data.releasePeriod;

                    let allTokens = new Decimal(res.data.frozenAmount).add(res.data.releasedAmount);
                    let percent = new Decimal(res.data.releasedAmount).div(allTokens.toString()).mul(100).floor();
                    this.released = percent.toNumber();
                } else if (HTTP_NO_CONTENT === res.status) {
                    this.releasePeriod = 10;
                    this.released = 20;
                }

                this.loading = false;
            })
            .catch((err) => {
                this.sendLogs('error', 'Can not load statistic data', err);
            });
    },
    methods: {
        updateTokenStatistics: function(newTokenStatistics) {
            this.setStats({
                releasePeriod: newTokenStatistics.releasePeriod,
                hourlyRate: newTokenStatistics.hourlyRate,
                releasedAmount: newTokenStatistics.releasedAmount,
                frozenAmount: newTokenStatistics.frozenAmount,
            });
            this.$axios.retry.get(this.$routing.generate('token_exchange_amount', {name: this.tokenName}))
            .then((res) => this.setTokenExchangeAmount(res.data))
            .catch((err) => {
                this.sendLogs('error', 'Can not load statistic data', err);
            });
        },
        saveReleasePeriod: function() {
            this.$axios.single.post(this.$routing.generate('lock_in', {
                name: this.tokenName,
            }), {
                released: this.released,
                releasePeriod: !this.showAreaUnlockedTokens ? 0 : this.releasePeriod,
            }).then((response) => {
                this.$emit('update', response);
                this.updateTokenStatistics(response.data);
                this.notifySuccess(this.$t('toasted.success.release_period_updated'));
            }).catch(({response}) => {
                if (!response) {
                    this.notifyError(this.$t('toasted.error.network'));
                    this.sendLogs('error', 'Save release period network error', response);
                } else if (response.data.message) {
                    this.notifyError(response.data.message);
                    this.sendLogs('error', 'Can not save release period', response);
                } else {
                    this.notifyError(this.$t('toasted.error.try_later'));
                    this.sendLogs('error', 'An error has occurred, please try again later', response);
                }
            });
        },
        ...mapMutations('tokenStatistics', [
            'setStats',
            'setTokenExchangeAmount',
        ]),
        refreshSliders: function() {
            this.$refs['released-slider'].refresh();
            this.$refs['release-period-slider'].refresh();
        },
    },
};
</script>

<style lang="scss" scoped>
    b {
        white-space: nowrap;
    }

    .statistic-description {
        font-size: 1.2rem;
    }
</style>
