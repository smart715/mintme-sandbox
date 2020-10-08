<template>
<div>
    <guide>
        <template slot="icon">
            <circle-progress
                class="circle-progress"
                :points-gained=tokenPointsGained
                :total-points=18
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
    name: 'TokenPointProgress',
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
    },
    components: {
        CircleProgress,
        Guide,
    },
    data() {
        return {
            tokenReleasePeriodPoint: 0,
        };
    },
    watch: {
        tokenReleasePeriodSet(value) {
            this.tokenReleasePeriodPoint = value;
        },
    },
    computed: {
        ...mapGetters('tokenStatistics', {
            releasePeriod: 'getReleasePeriod',
        }),
        tokenReleasePeriodSet: function() {
            if (typeof this.releasePeriod === 'number') return 4;
            return 0;
        },
        tokenDescriptionSet: function() {
            return this.tokenDescription ? 4 : 0;
        },
        socialMediaSet: function() {
            return this.tokenYoutube || this.tokenFacebook || this.tokenWebsite ? 2 : 0;
        },
        tokenDeployedSet: function() {
            return this.tokenStatus === tokenDeploymentStatus.deployed ? 4 : 0;
        },
        userProfileWithOutTradeAnonymouslySet: function() {
            if (
                this.profileName !== '' &&
                this.profileLastName !== '' &&
                this.profileDescription !== '' &&
                !this.profileAnonymously
            ) {
                return 4;
            }
            return 0;
        },
        tokenPointsGained: function() {
            return this.tokenReleasePeriodPoint +
                this.tokenDescriptionSet +
                this.socialMediaSet +
                this.tokenDeployedSet +
                this.userProfileWithOutTradeAnonymouslySet;
        },
    },
};
</script>
