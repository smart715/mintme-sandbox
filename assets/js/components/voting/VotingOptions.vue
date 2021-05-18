<template>
    <div class="card h-100">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <slot name="title">{{ $t('voting.options') }}</slot>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-column">
                <voting-option
                    v-for="(option, k) in options"
                    :key="k"
                    :option="option"
                    @update-option="updateOption(k, $event)"
                    @delete-option="deleteOption(k)"
                />
            </div>
            <div class="d-flex flex-column">
                <button
                    v-if="canAddOptions"
                    class="btn btn-primary m-2"
                    @click="addOption">
                    {{ $t('voting.add_option') }}
                </button>
                <button :disabled="disabledPublish" class="btn btn-primary m-2" @click="publish">
                    {{ $t('voting.publish') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import VotingOption from './VotingOption';
import {NotificationMixin, LoggerMixin} from '../../mixins';
import {mapGetters, mapActions} from 'vuex';

export default {
    name: 'VotingOptions',
    mixins: [NotificationMixin, LoggerMixin],
    components: {
        VotingOption,
    },
    data() {
        return {
            requesting: false,
        };
    },
    computed: {
        ...mapGetters('voting', {
            tokenName: 'getTokenName',
            votingData: 'getVotingData',
            options: 'getOptions',
            canAddOptions: 'canAddOptions',
            canDeleteOptions: 'canDeleteOptions',
            invalidForm: 'getInvalidForm',
        }),
        optionsCount() {
            return this.options.length;
        },
        invalidOptions() {
            return this.options.some((option) => !option.title || option.errorMessage);
        },
        disabledPublish() {
            return this.invalidOptions || this.invalidForm || this.requesting;
        },
    },
    methods: {
        publish() {
            this.requesting = true;
            this.$axios.single
                .post(this.$routing.generate('store_voting', {tokenName: this.tokenName}), this.votingData)
                .then(({data}) => {
                    this.unshiftVoting(data.voting);
                    this.resetVotingData();
                    this.notifySuccess(this.$t('voting.added_successfully'));
                })
                .catch((err) => {
                    this.notifyError(this.$t('toasted.error.try_later'));
                    this.sendLogs('error', err);
                })
                .then(() => this.requesting = false);
        },
        updateOption(key, option) {
            this.updateVotingOption({key, option});
        },
        ...mapActions('voting', [
            'addOption',
            'deleteOption',
            'unshiftVoting',
            'resetVotingData',
            'updateVotingOption',
        ]),
    },
};
</script>
