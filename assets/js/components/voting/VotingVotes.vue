<template>
    <div class="card">
        <div class="card-header">
            <h4 class="text-white">
                {{ $t('voting.votes') }}
                <span class="text-primary">
                    {{ votesCount }}
                </span>
            </h4>
        </div>
        <div class="card-body">
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
                        <div class="d-flex">
                            <avatar
                                :image="row.item.trader.profileAvatarUrl"
                                class="mr-1"
                                img-class="icon contract-avatar"
                            />
                            <elastic-text :value="row.item.trader.name" :url="getProfileUrl(row.item.trader.name)"/>
                        </div>
                    </template>
                    <template v-slot:cell(amount)="row">
                        {{ row.item.amount }}
                        <coin-avatar
                            :symbol="tokenName"
                            :is-crypto="!isToken"
                            :is-user-token="isToken"
                            :image="tokenAvatar"
                        />
                        {{ tokenName | rebranding }}
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
import CoinAvatar from '../CoinAvatar';
import Avatar from '../Avatar.vue';

export default {
    name: 'VotingVotes',
    mixins: [
        RebrandingFilterMixin,
    ],
    components: {
        BTable,
        ElasticText,
        CoinAvatar,
        Avatar,
    },
    props: {
        isToken: {
            type: Boolean,
            default: false,
        },
        tokenAvatar: String,
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
                    trader: {
                        name: uv.user.profile.nickname,
                        profileAvatarUrl: uv.user.profile.image.avatar_small,
                    },
                    option: uv.option.title,
                    amount: toMoney(uv.amountMoney, 2),
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
