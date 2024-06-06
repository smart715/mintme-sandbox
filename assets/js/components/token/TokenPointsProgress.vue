<template>
<div>
    <guide>
        <template slot="icon">
            <circle-progress
                class="circle-progress"
                :points-gained="tokenPointsGained"
                :total-points="100"
            />
        </template>
        <template slot="body">
            {{ $t('token.progress.body') }}
        </template>
    </guide>
</div>
</template>

<script>
import CircleProgress from '../../components/CircleProgress';
import Guide from '../Guide';
import {mapGetters} from 'vuex';
import {tokenDeploymentStatus} from '../../utils/constants';

export default {
    name: 'TokenPointsProgress',
    props: {
        profileName: String,
        profileLastname: String,
        profileDescription: String,
        profileAnonymously: String,
        tokenDescription: String,
        tokenFacebook: String,
        tokenYoutube: String,
        tokenWebsite: String,
        tokenStatus: String,
        isCreatedOnMintmeSite: Boolean,
        hasReleasePeriod: Boolean,
    },
    components: {
        CircleProgress,
        Guide,
    },
    computed: {
        ...mapGetters('tokenStatistics', {
            releasePeriod: 'getReleasePeriod',
        }),
        tokenReleasePeriodSet: function() {
            return this.hasReleasePeriod
                || 'number' === typeof this.releasePeriod
                || !this.isCreatedOnMintmeSite ? 22 : 0;
        },
        tokenDescriptionSet: function() {
            return this.tokenDescription ? 22 : 0;
        },
        socialMediaSet: function() {
            return this.tokenYoutube
                || this.tokenFacebook
                || this.tokenWebsite ? 12 : 0;
        },
        tokenDeployedSet: function() {
            return this.tokenStatus === tokenDeploymentStatus.deployed ? 22 : 0;
        },
        userProfileWithOutTradeAnonymouslySet: function() {
            return !this.profileAnonymously
                && '' !== this.profileName
                && '' !== this.profileLastName
                && '' !== this.profileDescription ? 22 : 0;
        },
        tokenPointsGained: function() {
            return this.tokenReleasePeriodSet +
                this.tokenDescriptionSet +
                this.socialMediaSet +
                this.tokenDeployedSet +
                this.userProfileWithOutTradeAnonymouslySet;
        },
    },
};
</script>
