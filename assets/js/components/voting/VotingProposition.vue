<template>
    <div @click="goToShow" class="w-100 c-pointer">
        <div class="d-flex align-items-center">
            <div class="badge badge-primary h-100 py-1 px-4 mr-3">{{ status }}</div>
            <h4 class="p-0 m-0">{{ proposition.title }}</h4>
        </div>
        <div class="pt-1">
            {{ info }}
        </div>
    </div>
</template>

<script>
import {mapGetters, mapMutations} from 'vuex';
import {GENERAL} from '../../utils/constants';
import moment from 'moment';

export default {
    name: 'VotingProposition',
    props: {
        proposition: Object,
    },
    computed: {
        ...mapGetters('voting', {
            tokenName: 'getTokenName',
        }),
        info() {
            return this.$t('voting.proposition.info', {
                nickname: this.proposition.creatorProfile.nickname,
                startDate: moment(this.proposition.createdAt).format(GENERAL.dateTimeFormat),
                endDate: moment(this.proposition.endDate).format(GENERAL.dateTimeFormat),
            });
        },
        status() {
            return this.proposition.closed
                ? this.$t('voting.proposition.closed')
                : this.$t('voting.proposition.active')
                ;
        },
    },
    methods: {
        ...mapMutations('voting', [
            'setCurrentVoting',
        ]),
        goToShow() {
            this.setCurrentVoting(this.proposition);
            this.$emit('go-to-show');
        },
    },
};
</script>
