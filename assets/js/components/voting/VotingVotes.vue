<template>
    <div class="card">
        <div class="card-header">
            {{ $t('voting.votes') }} ({{ votesCount }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <b-table
                    :items="votes"
                    :fields="fields"
                    thead-class="d-none"
                >
                    <template v-slot:cell(option)="row">
                        <elastic-text :value="row.item.option"/>
                    </template>
                    <template v-slot:cell(trader)="row">
                        <elastic-text :value="row.item.trader" :url="getProfileUrl(row.item.trader)"/>
                    </template>
                </b-table>
            </div>
        </div>
    </div>
</template>

<script>
import {BTable} from 'bootstrap-vue';
import {mapGetters} from 'vuex';
import {RebrandingFilterMixin} from '../../mixins';
import ElasticText from '../ElasticText';
import {toMoney} from '../../utils';

export default {
    name: 'VotingVotes',
    mixins: [
        RebrandingFilterMixin,
    ],
    components: {
        BTable,
        ElasticText,
    },
    data() {
        return {
            fields: [
                {
                    key: 'trader',
                },
                {
                    key: 'option',
                },
                {
                    key: 'amount',
                },
            ],
        };
    },
    computed: {
        ...mapGetters('voting', {
            voting: 'getCurrentVoting',
            tokenName: 'getTokenName',
        }),
        votesCount() {
            return this.voting.userVotings.length;
        },
        votes() {
            return this.voting.userVotings.map((uv) => {
                return {
                    trader: uv.user.profile.nickname,
                    option: uv.option.title,
                    amount: `${toMoney(uv.amountMoney, 2)} ${this.rebrandingFunc(this.tokenName)}`,
                };
            });
        },
    },
    methods: {
        getProfileUrl(nickname) {
            return this.$routing.generate('profile-view', {nickname});
        },
    },
};
</script>
