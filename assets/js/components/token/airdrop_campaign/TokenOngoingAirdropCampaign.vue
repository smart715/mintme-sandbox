<template>
    <div class="col-md-10 bg-green">
        <div class="container">
            <div class="row">
                <div class="col-3 align-middle">
                    <h3>Ongoing airdrop!</h3>
                </div>
                <div class="col-6 align-middle">
                    <p class="m-0 text-white">
                        For first {{ 1 }} participants {{ 1 }} {{ tokenName }} for free.
                        Currently {{ 1 }}/{{ 100 }} participants.
                    </p>
                    <p class="m-0 text-white">Airdrop ands on {{ 1 }}</p>
                </div>
                <div class="col-3 text-right align-middle">
                    <button
                        @click="claimCampaign"
                        class="btn btn-primary">
                        Participate
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import Decimal from 'decimal.js';
import {LoggerMixin, NotificationMixin, MoneyFilterMixin} from '../../../mixins';
import {GENERAL} from '../../../utils/constants';

export default {
    name: 'TokenOngoingAirdropCampaign',
    mixins: [NotificationMixin, LoggerMixin, MoneyFilterMixin],
    components: {
    },
    props: {
        tokenName: String,
    },
    data() {
        return {
            airdropCampaign: null,
        };
    },
    mounted: function() {
    },
    computed: {
    },
    watch: {
    },
    methods: {
        claimCampaign: function() {

        },
    },
};
</script>

