<template>
    <div class="row">
        <div class="col-12 row mb-3 pr-0">
            <div class="col-md-6 mt-3">
                <slot name="title">
                    <h2 class="text-white">
                        {{ $t('voting.create') }}
                        <span class="text-primary"> {{ $t('voting.voting') }} </span>
                    </h2>
                </slot>
            </div>
            <div class="col-md-6 d-flex pr-0 btn-new-proposition mt-3">
                <a
                    class="btn btn-dark text-white font-weight-bold mr-2 my-2"
                    :class="{'d-none': isTokenPage}"
                    :href="listVotingUrl"
                    tabindex="2"
                >
                    <div class="pl-2 pr-2 pt-1 pb-1">
                        <font-awesome-icon icon="arrow-left" />
                        <span class="text-uppercase">
                            {{ $t('go_back') }}
                        </span>
                    </div>
                </a>
                <m-button
                    type="primary"
                    tabindex="2"
                    :disabled="disabledPublish"
                    :loading="requesting"
                    @click="publish"
                >
                    <div class="pl-2 pr-2 pt-1 pb-1">
                        <font-awesome-icon icon="check-square" />
                        {{ $t('voting.publish') }}
                    </div>
                </m-button>
            </div>
        </div>
        <voting-form
            class="mb-3 col-12 p-0 col-lg-7"
        />
        <voting-options
            class="col-12 col-lg-5"
        />
    </div>
</template>

<script>
import VotingForm from './VotingForm';
import VotingOptions from './VotingOptions';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import {
    NotificationMixin,
    VotingInitMixin,
    RebrandingFilterMixin,
} from '../../mixins';
import {
    faArrowLeft,
    faCheckSquare,
} from '@fortawesome/free-solid-svg-icons';
import {
    mapGetters,
    mapActions,
} from 'vuex';
import {MButton} from '../UI';
import {flattenJSON} from '../../utils';

library.add(
    faArrowLeft,
    faCheckSquare,
);

export default {
    name: 'VotingCreate',
    components: {
        VotingOptions,
        VotingForm,
        FontAwesomeIcon,
        MButton,
    },
    mixins: [
        VotingInitMixin,
        NotificationMixin,
        RebrandingFilterMixin,
    ],
    props: {
        isTokenPage: {
            type: Boolean,
            default: false,
        },
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
            invalidForm: 'getInvalidForm',
            invalidOptions: 'getInvalidOptions',
        }),
        disabledPublish() {
            return this.invalidOptions || this.invalidForm || this.requesting;
        },
        listVotingUrl() {
            return this.$routing.generate('voting');
        },
    },
    methods: {
        ...mapActions('voting', [
            'updateEndDate',
            'clearVotingData',
        ]),
        async publish() {
            this.requesting = true;

            try {
                const {data} = await this.$axios.single.post(
                    this.$routing.generate(
                        'store_voting',
                        {tokenName: this.tokenName},
                    ),
                    this.votingData,
                );

                this.clearVotingData();
                this.notifySuccess(this.$t('voting.added_successfully'));
                this.redirectToShowVotings(data.voting);
            } catch (err) {
                const errors = err.response?.data?.errors?.children;

                if (errors) {
                    const flattenErrors = flattenJSON(errors);

                    if (0 < Object.keys(flattenErrors).length) {
                        this.notifyError(flattenErrors[Object.keys(flattenErrors)[0]]);

                        return;
                    }
                }

                this.notifyError(err.response?.data?.message || this.$t('toasted.error.try_later'));
                this.$logger.error('Error while creating vote', err);
            } finally {
                this.requesting = false;
            }
        },
        redirectToShowVotings(voting) {
            if (this.isTokenPage) {
                this.$emit('voting-created', voting);

                return;
            }

            window.location.href = this.$routing.generate('show_voting', {slug: voting.slug});
        },
    },
};
</script>
