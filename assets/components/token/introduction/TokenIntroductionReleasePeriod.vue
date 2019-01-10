<template>
    <b-row>
        <b-col cols="12" class="statistic-description mb-2">
            <div>
                Token release period:
                <guide>
                    <font-awesome-icon
                        icon="question"
                        slot='icon'
                        class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                    <template slot="header">
                        Token Release Period
                    </template>
                    <template slot="body">
                        Period it will take for the full release of your newly created token,
                        something similar to escrow. Mintme acts as 3rd party that ensure
                        you won’t flood market with all of your tokens which could lower price
                        significantly, because unlocking all tokens take time. It’s released hourly
                    </template>
                </guide>
            </div>
            <div>
                explanation about token release period. Three maybe four sentences. how it works.
            </div>
        </b-col>
        <b-col cols="12">
            <div>Amount released at beginning: {{ released }}%</div>
            <b-row class="mx-1">
                <b-col cols="1" class="p-0">
                    <b>1%</b>
                </b-col>
                <b-col class="p-0">
                    <vue-slider
                        ref="released-slider"
                        :disabled="releasedDisabled"
                        v-model="released"
                        :min="1" :max="100"
                        :interval="1"
                        :tooltip="false"
                        width="100%">
                    </vue-slider>
                </b-col>
                <b-col cols="1" class="px-0 ml-1">
                    <b>100%</b>
                </b-col>
            </b-row>
        </b-col>
        <b-col cols="12">
            <div>Time needed to unlock all tokens: {{ period }} years</div>
            <b-row class="mx-1">
                <b-col cols="1" class="p-0">
                    <font-awesome-icon icon="unlock-alt" class="ml-1 mb-1" />
                </b-col>
                <b-col class="p-0">
                    <vue-slider
                        ref="release-period-slider"
                        v-model="period"
                        :min="10"
                        :max="80"
                        :interval="10"
                        :tooltip="false"
                        width="100%">
                    </vue-slider>
                </b-col>
                <b-col cols="1" class="p-0" ml-1>
                    <font-awesome-icon icon="lock" class="ml-1 mb-1" />
                </b-col>
            </b-row>
        </b-col>
        <b-col cols="12" class="mt-3">
            <div class="text-right">
                <b-button type="submit" class="px-4" variant="primary" @click="saveReleasePeriod">Save</b-button>
                <b-button class="px-4" @click="cancelAction">Cancel</b-button>
            </div>
        </b-col>
    </b-row>
</template>

<script>
import vueSlider from 'vue-slider-component';
import axios from 'axios';
import {deepFlatten} from '../../../js/utils';
import Guide from '../../Guide';

export default {
    name: 'TokenIntroductionReleasePeriod',
    props: {
        releasePeriodRoute: String,
        csrf: String,
        releasedDisabled: {type: Boolean, default: false},
        period: {type: Number, default: 10},
    },
    data() {
        return {
            released: 1,
        };
    },
    components: {
        vueSlider,
        Guide,
    },
    methods: {
        cancelAction: function() {
            this.$emit('cancel');
        },
        saveReleasePeriod: function() {
            axios.post(this.releasePeriodRoute, {
                released: this.released,
                releasePeriod: this.period,
                _csrf_token: this.csrf,
            }).then((response) => {
                this.$emit('onStatsUpdate', response);
                this.$toasted.success('Release period updated.');
                this.cancelAction();
            }).catch((error) => {
                if (400 === error.response.status) {
                    deepFlatten(error.response.data.errors).forEach((err) => {
                        this.$toasted.error(err);
                    });
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
