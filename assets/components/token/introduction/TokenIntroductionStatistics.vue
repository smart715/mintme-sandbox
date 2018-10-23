<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header">
                Statistics
                <guide>
                    <div slot="header">
                        <h5 class="font-bold">Statistics Guide</h5>
                    </div>
                    <div slot="body">
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</p>
                    </div>
                </guide>
                <span class="card-header-icon">
                    <font-awesome-icon
                        v-if="editable && !showSettings"
                        class="icon float-right c-pointer"
                        size="2x"
                        icon="edit"
                        transform="shrink-4 up-1.5"
                        @click="switchAction"
                        />
                </span>
            </div>
            <div class="card-body">
                <div v-if="!showSettings" class="row">
                    <div class="col">
                        <div class="font-weight-bold pb-4">
                            Profile Statistics
                        </div>
                        <div class="pb-1">
                            Wallet on exchange: xxx
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                                <template slot="header">
                                    Wallet on exchange Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Active orders: xxx
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                                <template slot="header">
                                    Active orders Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Withdrawn: xxx
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                                <template slot="header">
                                    Withdrawn Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Sold on the market: xxx
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                                <template slot="header">
                                    Sold on the market Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>

                        </div>
                    </div>
                    <div class="col">
                        <div class="font-weight-bold pb-4">
                            Token Release Statistics
                        </div>
                        <div class="pb-1">
                            Release period: {{ stats.releasePeriod }}
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                                <template slot="header">
                                    Release period Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Hourly installment: {{ stats.hourlyRate }}
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                                <template slot="header">
                                    Hourly installment Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Already released: {{ stats.releasedAmount }}
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                                <template slot="header">
                                    Already released Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </div>
                        <div class="pb-1">
                            Remaining: {{ stats.frozenAmount }}
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                    rounded-circle square blue-question"/>
                                <template slot="header">
                                    Remaining Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <release-period-component
                        :csrf="csrf"
                        :release-period-route="releasePeriodRoute"
                        :period="statsPeriod"
                        :released-disabled="releasedDisabled"
                        @cancel="switchAction"
                        @onStatsUpdate="statsUpdated">
                    </release-period-component>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ReleasePeriodComponent from './TokenIntroductionReleasePeriod';
import Guide from '../../Guide';

const defaultValue = 'xxx';

export default {
    name: 'TokenIntroductionStatistics',
    components: {
        ReleasePeriodComponent,
        Guide,
    },
    props: {
        releasePeriodRoute: String,
        csrf: String,
        containerClass: String,
        editable: Boolean,
        stats: {
            type: Object,
            default: function() {
                return {
                    releasePeriod: defaultValue,
                    hourlyRate: defaultValue,
                    releasedAmount: defaultValue,
                    frozenAmount: defaultValue,
                };
            },
        },
    },
    data() {
        return {
            showSettings: false,
        };
    },
    methods: {
        switchAction: function() {
            this.showSettings = !this.showSettings;
        },
        statsUpdated: function(res) {
            this.stats = res.data;
        },
    },
    computed: {
        releasedDisabled: function() {
            return this.stats.releasePeriod !== defaultValue;
        },
        statsPeriod: function() {
            return !this.releasedDisabled ? 10 : this.stats.releasePeriod;
        },
    },
};
</script>
