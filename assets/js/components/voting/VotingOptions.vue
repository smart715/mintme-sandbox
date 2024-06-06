<template>
    <div class="row ml-0 mr-0">
        <div class="col-md-12 p-0">
            <div class="card">
                <div class="m-4">
                    <div class="mb-4">
                        <slot name="title">
                            <h3>
                                {{ $t('voting.options.set') }}
                                <span class="text-primary">{{ $t('voting.options') }}</span>
                                <guide class="tooltip-center">
                                    <template slot="body">
                                        {{ $t('voting.tooltip.options') }}
                                    </template>
                                </guide>
                            </h3>
                        </slot>
                    </div>
                    <div class="form-group">
                        <voting-option
                            v-for="(option, k) in options"
                            :key="k"
                            :element="k"
                            :option="option"
                            @update-option="updateOption(k, $event)"
                            @delete-option="deleteOption(k)"
                        />
                    </div>
                    <div class="form-group">
                        <div class="d-flex justify-content-center">
                            <button
                                v-if="canAddOptions"
                                class="btn btn-primary m-2"
                                tabindex="1"
                                @click="addOption"
                            >
                                <div class="pl-2 pr-2 pt-1 pb-1">
                                    <font-awesome-icon icon="plus-square" />
                                    {{ $t('voting.add_option') }}
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import VotingOption from './VotingOption';
import {NotificationMixin} from '../../mixins';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faPlusSquare} from '@fortawesome/free-solid-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';
import {mapGetters, mapActions, mapMutations} from 'vuex';
import {WEB} from '../../utils/constants';
import Guide from '../Guide';

library.add(faPlusSquare);

export default {
    name: 'VotingOptions',
    mixins: [NotificationMixin],
    components: {
        Guide,
        VotingOption,
        FontAwesomeIcon,
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
        isToken() {
            return this.tokenName !== WEB.symbol;
        },
    },
    methods: {
        updateOption(key, option) {
            this.updateVotingOption({key, option});
        },
        ...mapActions('voting', [
            'addOption',
            'deleteOption',
            'updateVotingOption',
        ]),
        ...mapMutations('voting', [
            'setInvalidOptions',
        ]),
    },
    watch: {
        invalidOptions(value) {
            this.setInvalidOptions(value);
        },
    },
};
</script>
