<template>
    <div class="row">
        <div class="card col-sm-12 col-lg-7 mt-3">
            <div class="card-header">{{ voting.title }}</div>
            <div class="card-body">
                <bbcode-view :value="voting.description"/>
            </div>
        </div>
        <div class="card col-sm-12 col-lg-5 mt-3">
            <div class="card-header">{{ $t('voting.information') }}</div>
            <div class="card-body">
                {{ $t('voting.form.start_date') }} {{ startDate }} <br>
                {{ $t('voting.form.end_date') }} {{ endDate }} <br>
                {{ $t('voting.form.created_by') }}
                <a :href="profileUrl">{{ voting.creatorProfile.nickname }}</a>
            </div>
        </div>
        <voting-vote v-if="!voting.closed" class="col-sm-12 col-lg-7 mt-3"/>
        <voting-result :class="{'col-lg-5': !voting.closed}" class="col-sm-12 mt-3"/>
    </div>
</template>

<script>
import VotingVote from './VotingVote';
import VotingResult from './VotingResult';
import BbcodeView from '../bbcode/BbcodeView';
import {GENERAL} from '../../utils/constants';
import {VotingInitMixin} from '../../mixins';
import {mapGetters, mapMutations} from 'vuex';
import moment from 'moment';

export default {
    name: 'VotingView',
    components: {
        VotingResult,
        VotingVote,
        BbcodeView,
    },
    mixins: [
        VotingInitMixin,
    ],
    props: {
        votingProp: {
            type: Object,
            default: () => {},
        },
    },
    computed: {
        ...mapGetters('voting', {
            voting: 'getCurrentVoting',
        }),
        startDate() {
            return moment(this.voting.createdAt).format(GENERAL.dateTimeFormat);
        },
        endDate() {
            return moment(this.voting.endDate).format(GENERAL.dateTimeFormat);
        },
        profileUrl() {
            return this.$routing.generate('profile-view', {nickname: this.voting.creatorProfile.nickname});
        },
    },
    methods: {
        ...mapMutations('voting', [
            'setCurrentVoting',
        ]),
    },
    created() {
        if (!this.voting.id && this.votingProp.id) {
            this.setCurrentVoting(this.votingProp);
        }
    },
};
</script>
