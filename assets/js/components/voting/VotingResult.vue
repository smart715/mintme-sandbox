<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <slot name="title">
                    <h3 class="text-white">
                        {{ $t('voting.current') }}
                        <span class="text-primary">
                            {{ $t('voting.current.results') }}
                        </span>
                    </h3>
                </slot>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-column">
                <div v-for="(option, key) in options" :key="key" class="m-2">
                    <div>
                        {{ option.title }} {{ option.amount }}
                        <coin-avatar
                            :symbol="tokenName"
                            :is-crypto="!isToken"
                            :is-user-token="isToken"
                            :image="tokenAvatar"
                        />
                        {{ tokenName | rebranding }}
                    </div>
                    <b-progress
                        :max="100"
                        class="w-100 rounded-0"
                        height="20px"
                    >
                        <b-progress-bar
                            class="font-weight-bold text-white"
                            :value="option.percentage"
                            :label="option.percentage + '%'"
                            variant="primary"
                        />
                    </b-progress>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {BProgress, BProgressBar} from 'bootstrap-vue';
import {mapGetters} from 'vuex';
import {RebrandingFilterMixin} from '../../mixins';
import {toMoney} from '../../utils';
import CoinAvatar from '../CoinAvatar';

export default {
    name: 'VotingResult',
    props: {
        isToken: {
            type: Boolean,
            default: false,
        },
        tokenAvatar: String,
    },
    components: {
        BProgress,
        BProgressBar,
        CoinAvatar,
    },
    mixins: [
        RebrandingFilterMixin,
    ],
    computed: {
        ...mapGetters('voting', {
            voting: 'getCurrentVoting',
            tokenName: 'getTokenName',
        }),
        totalAmount() {
            return this.voting.userVotings.reduce((acc, userVoting) => acc + parseFloat(userVoting.amountMoney), 0);
        },
        optionAmounts() {
            const amounts = {};
            this.voting.userVotings.forEach((userVoting) => {
                const optionId = userVoting.option.id;
                amounts[optionId] = (amounts[optionId] || 0) + parseFloat(userVoting.amountMoney);
            });

            return amounts;
        },
        options() {
            return this.voting.options.map((option) => {
                return {
                    ...option,
                    amount: toMoney(this.optionAmounts[option.id] || 0, 0),
                    percentage: Math.floor(
                        (this.optionAmounts[option.id] || 0) / (this.totalAmount || 1) * 100 * 10
                    ) / 10,
                };
            });
        },
    },
};
</script>
