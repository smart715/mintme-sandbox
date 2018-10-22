<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header">
                Statistics
                <span class="text-white">
                    <font-awesome-icon
                        icon="question"
                        class="m-0 p-1 h4 bg-orange rounded-circle square"
                    />
                </span>
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
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Active orders: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Withdrawn: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Sold on the market: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                    </div>
                    <div class="col">
                        <div class="font-weight-bold pb-4">
                            Token Release Statistics
                        </div>
                        <div class="pb-1">
                            Release period: {{ stats.releasePeriod }}
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Hourly installment: {{ stats.hourlyRate }}
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Already released: {{ stats.releasedAmount }}
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Remaining: {{ stats.frozenAmount }}
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
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

const defaultValue = 'xxx';

export default {
    name: 'TokenIntroductionStatistics',
    components: {
        ReleasePeriodComponent,
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
