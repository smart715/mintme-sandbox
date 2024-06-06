<template>
    <div>
        <b-row>
            <b-col cols="12">
                <div>
                    {{ $t('token.release_period.amount', {released}) }}
                    <guide class="tooltip-center">
                        <template slot="body">
                            {{ $t('token.release_period.tooltip.amount') }}
                        </template>
                    </guide>
                </div>
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
                            :tooltip="'none'"
                            width="100%"
                        />
                    </b-col>
                    <b-col cols="2" class="text-center px-0">
                        <b>100%</b>
                    </b-col>
                </b-row>
            </b-col>
            <b-col v-bind:class="{invisible: !showAreaUnlockedTokens}" cols="12">
                <div>
                    {{ $t('token.release_period.for_rest_years', {releasePeriod}) }}
                    <guide class="tooltip-center">
                        <template slot="body">
                            {{ $t('token.release_period.tooltip.for_rest_years') }}
                        </template>
                    </guide>
                </div>
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
                            :tooltip="'none'"
                            width="100%"
                        />
                    </b-col>
                    <b-col cols="2" class="text-center px-0">
                        <font-awesome-icon icon="lock" class="ml-1 mb-1" />
                    </b-col>
                </b-row>
            </b-col>
            <b-col cols="12" class="mt-3">
                <div class="d-flex align-items-center">
                    <m-button
                        v-if="isTokenNotDeployed"
                        class="px-4 mr-1"
                        type="primary"
                        wide
                        :disabled="releasePeriodDisabled"
                        :loading="loading || isSaving"
                        @click="saveReleasePeriod"
                    >
                        {{ $t('save') }}
                    </m-button>
                </div>
            </b-col>
        </b-row>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faLock, faUnlockAlt} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Decimal from 'decimal.js';
import vueSlider from 'vue-slider-component';
import 'vue-slider-component/theme/default.css';
import {BRow, BCol} from 'bootstrap-vue';
import {NotificationMixin} from '../../mixins';
import {HTTP_OK, HTTP_NO_CONTENT} from '../../utils/constants.js';
import {mapMutations} from 'vuex';
import {MButton} from '../UI';
import Guide from '../Guide';

library.add(faLock, faUnlockAlt);

export default {
    name: 'TokenReleasePeriod',
    components: {
        Guide,
        BRow,
        BCol,
        MButton,
        vueSlider,
        FontAwesomeIcon,
    },
    mixins: [
        NotificationMixin,
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
            isSaving: false,
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

                    const allTokens = new Decimal(res.data.frozenAmount).add(res.data.releasedAmount);
                    const percent = new Decimal(res.data.releasedAmount).div(allTokens.toString()).mul(100).floor();
                    this.released = percent.toNumber();
                    this.setHasReleasePeriod(true);
                } else if (HTTP_NO_CONTENT === res.status) {
                    this.releasePeriod = 10;
                    this.released = 20;
                }

                this.loading = false;
            })
            .catch((err) => {
                this.$logger.error('Can not load statistic data', err);
            });
    },
    methods: {
        ...mapMutations('tokenStatistics', [
            'setStats',
            'setTokenExchangeAmount',
        ]),
        ...mapMutations('tokenSettings', [
            'setHasReleasePeriod',
        ]),
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
                    this.$logger.error('Can not load statistic data', err);
                });
        },
        saveReleasePeriod: function() {
            this.isSaving = true;
            this.$axios.single.post(this.$routing.generate('lock_in', {
                name: this.tokenName,
            }), {
                released: this.released,
                releasePeriod: !this.showAreaUnlockedTokens ? 0 : this.releasePeriod,
            }).then((response) => {
                this.setHasReleasePeriod(true);
                this.updateTokenStatistics(response.data);
                this.notifySuccess(this.$t('toasted.success.release_period_updated'));
            }).catch(({response}) => {
                if (!response) {
                    this.notifyError(this.$t('toasted.error.network'));
                    this.$logger.error('Save release period network error', response);
                } else if (response.data.message) {
                    this.notifyError(response.data.message);
                    this.$logger.error('Can not save release period', response);
                } else {
                    this.notifyError(this.$t('toasted.error.try_later'));
                    this.$logger.error('An error has occurred, please try again later', response);
                }
            }).finally(() => {
                this.isSaving = false;
            });
        },
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
