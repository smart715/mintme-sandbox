<template>
    <div class="row">
        <voting-form class="col-sm-12 col-lg-7"/>
        <voting-options class="col-sm-12 col-lg-5"/>
    </div>
</template>

<script>
import VotingForm from './VotingForm';
import VotingOptions from './VotingOptions';
import {VotingInitMixin} from '../../mixins';
import {mapActions} from 'vuex';

export default {
    name: 'VotingCreate',
    components: {
        VotingOptions,
        VotingForm,
    },
    mixins: [
        VotingInitMixin,
    ],
    methods: {
        ...mapActions('voting', [
            'updateEndDate',
        ]),
    },
    mounted() {
        this.updateEndDate();
    },
};
</script>
