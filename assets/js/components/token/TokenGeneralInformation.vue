<template>
    <div class="d-flex align-items-center token-general-information">
        <token-deploy-icon
            :is-mintme="isMintmeToken"
            :token-crypto="tokenCrypto"
            class="ml-2"
            :is-owner="isOwner"
            :status-prop="getDeploymentStatus"
            :token-name="tokenName"
            :logged-in="loggedIn"
        />
        <token-points-progress
            :profile-name="profileName"
            :profile-lastname="profileLastname"
            :profile-description="profileDescription"
            :profile-anonymously="profileAnonymously"
            :token-description="tokenDescription"
            :token-facebook="tokenFacebook"
            :token-youtube="tokenYoutube"
            :token-website="tokenWebsite"
            :token-status="getDeploymentStatus"
            :is-mintme-token="isMintmeToken"
            :has-release-period="hasReleasePeriodProp"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
        />
    </div>
</template>
<script>
import {DEPLOY_PENDING_LS_KEY, webSymbol} from '../../utils/constants';
import TokenDeployIcon from './deploy/TokenDeployIcon';
import TokenPointsProgress from './TokenPointsProgress';
import {mapGetters, mapMutations} from 'vuex';
import {NotificationMixin} from '../../mixins';

export default {
    name: 'TokenGeneralInformation',
    mixins: [
        NotificationMixin,
    ],
    props: {
        isOwner: Boolean,
        hasReleasePeriodProp: Boolean,
        isCreatedOnMintmeSite: Boolean,
        market: Object,
        profileName: String,
        profileLastname: String,
        profileDescription: String,
        profileAnonymously: String,
        tokenDescription: String,
        tokenFacebook: String,
        tokenYoutube: String,
        tokenWebsite: String,
        tokenName: String,
        tokenDeleteSoldLimit: Number,
        minimumOrder: String,
        tokenDeploys: Array,
        websocketUrl: String,
        tokenAvatar: String,
        viewOnly: Boolean,
        loadDiscordRoles: Boolean,
        loggedIn: Boolean,
    },
    components: {
        TokenDeployIcon,
        TokenPointsProgress,
    },
    created() {
        this.setDeploys(this.tokenDeploys);
    },
    mounted() {
        this.setMinOrder(this.minimumOrder);

        this.setTokenDeleteSoldLimit(this.tokenDeleteSoldLimit);

        const current = this.tokenDeploys.find((deploy) => deploy.pending);

        if (current) {
            this.handleDeployEvent(current.crypto.symbol);
        }

        const cachedDeployedPending = localStorage.getItem(DEPLOY_PENDING_LS_KEY);

        if (cachedDeployedPending) {
            this.handleDeployEvent(cachedDeployedPending);
        }
    },
    data() {
        return {
            deployTimeout: null,
        };
    },
    computed: {
        ...mapGetters('tokenInfo', [
            'getDeploymentStatus',
            'getMainDeploy',
            'getDeploys',
        ]),
        isMintmeToken() {
            return this.getMainDeploy
                ? webSymbol === this.getMainDeploy.crypto.symbol
                : false;
        },
        tokenCrypto() {
            return this.getMainDeploy
                ? this.getMainDeploy.crypto
                : null;
        },
    },
    methods: {
        ...mapMutations('tokenStatistics', [
            'setTokenDeleteSoldLimit',
        ]),
        ...mapMutations('tokenInfo', [
            'setDeploys',
        ]),
        ...mapMutations('minOrder', [
            'setMinOrder',
        ]),
        fetchDeploys() {
            return this.$axios.retry.get(
                this.$routing.generate('token_deploys', {
                    name: this.tokenName,
                })
            )
                .then((response) => response.data);
        },
        handleTokenDeployCache(crypto, current, deploys) {
            // if not pending, remove from localStorage, otherwise, save to localStorage
            if (!current || (!current.pending && 1 <= deploys.length)) {
                localStorage.removeItem(DEPLOY_PENDING_LS_KEY);
            } else if (!localStorage.getItem(DEPLOY_PENDING_LS_KEY)) {
                localStorage.setItem(DEPLOY_PENDING_LS_KEY, crypto);
            }
        },
        handleDeployEvent(crypto) {
            clearTimeout(this.deployTimeout);

            this.fetchDeploys()
                .then((deploys) => {
                    this.setDeploys(deploys);

                    const current = deploys.find((deploy) => crypto === deploy.crypto.symbol);

                    if (this.isOwner) {
                        this.handleTokenDeployCache(crypto, current, deploys);

                        if (!current && 0 === deploys.length) {
                            // main deploy failed
                            return this.notifyError(this.$t('toasted.error.can_not_be_deployed'));
                        }

                        if (!current && 1 <= deploys.length) {
                            // connection failed
                            return this.notifyError(this.$t('toasted.error.can_not_be_connected'));
                        }

                        if (current && !current.pending && 1 === deploys.length) {
                            // main deploy succeed
                            return this.$emit('show-deployed-modal');
                        }

                        if (current && !current.pending && 1 < deploys.length) {
                            // connection succeed
                            return this.notifySuccess(this.$t('token.connect.successed'));
                        }
                    }

                    if (current && current.pending) {
                        this.deployTimeout = setTimeout(
                            () => this.handleDeployEvent(crypto),
                            60000
                        );
                    }
                })
                .catch((err) => {
                    clearTimeout(this.deployTimeout);
                    this.notifyError(this.$t('toasted.error.network'));
                });
        },
    },
};
</script>
