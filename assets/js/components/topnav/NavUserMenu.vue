<template>
    <div>
        <a :class="linkClasses.wallet" :href="routes.wallet">
            <font-awesome-icon
                icon="wallet"
                class="dropdown-link-icon float-left mt-xl-1 mr-2 d-inline"
            />
            {{ $t('navbar.wallet') }}
        </a>
        <a
            class="d-flex align-items-center justify-content-start"
            :class="linkClasses.voting"
            :href="routes.voting"
        >
            <font-awesome-icon
                icon="gavel"
                class="dropdown-link-icon float-left mt-xl-1 mr-2 d-inline"
            />
            {{ $t('voting.voting') }}
            <fetchable-counter
                class="mb-2"
                :url-data="routes.votingCount"
                block
                :icon="false"
            />
        </a>
        <a :class="linkClasses.referral_program" :href="routes.referral_program">
            <font-awesome-icon
                icon="users"
                class="dropdown-link-icon float-left mt-xl-1 mr-2 d-inline"
            />
            {{ $t('navbar.referrals') }}
        </a>
        <a :class="linkClasses.profile" :href="routes.profile" tabindex="0">
            <font-awesome-icon
                icon="user"
                class="dropdown-link-icon float-left mt-xl-1 mr-2 d-inline"
            />
            {{ $t('navbar.profile') }}
        </a>
        <a :class="linkClasses.settings" :href="routes.settings">
            <font-awesome-icon
                icon="cog"
                class="dropdown-link-icon float-left mt-xl-1 mr-2 d-inline"
            />
            {{ $t('navbar.settings') }}
        </a>
        <a v-if="hasTokens" :class="linkClasses.token_settings" :href="routes.token_settings">
            <font-awesome-icon
                icon="tools"
                class="dropdown-link-icon float-left mt-xl-1 mr-2 d-inline"
            />
            {{ $t('navbar.token_settings') }}
        </a>
        <form
            :action="routes.logout"
            method="post"
            class="log-out-form"
            tabindex="0"
            :id="logOutFormId"
        >
            <input type="hidden" name="_csrf_token" :value="csrfToken"/>
            <a :class="linkClasses.logout">
                <button
                    class="logout"
                    type="submit"
                    name="_submit"
                    tabindex="-1"
                >
                    <font-awesome-icon
                        icon="sign-out-alt"
                        class="dropdown-link-icon float-left mt-xl-1 mr-2 d-inline"
                    />
                    {{ $t('log_out') }}
                </button>
            </a>
        </form>
    </div>
</template>

<script>
import {directive as onClickaway} from 'vue-clickaway';
import {faGavel, faUser, faCog, faTools, faSignOutAlt, faUsers} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import FetchableCounter from '../FetchableCounter';
import {LOGOUT_FORM_ID} from '../../utils/constants';

library.add(
    faGavel,
    faUser,
    faUsers,
    faCog,
    faTools,
    faSignOutAlt,
);

export default {
    name: 'NavUserMenu',
    components: {
        FontAwesomeIcon,
        FetchableCounter,
    },
    directives: {
        onClickaway,
    },
    props: {
        nickname: String,
        tokenName: String,
        hasTokens: Boolean,
        csrfToken: String,
        avatarUrl: String,
        route: String,
        linkClass: {
            type: String,
            default: 'dropdown-item',
        },
    },
    data() {
        return {
            routes: {
                wallet: this.$routing.generate('wallet'),
                feed: this.$routing.generate('show_user_feed'),
                voting: this.$routing.generate('voting'),
                votingCount: this.$routing.generate('voting_count'),
                referral_program: this.$routing.generate('referral-program'),
                profile: this.$routing.generate('profile-view', {nickname: this.nickname}),
                settings: this.$routing.generate('settings'),
                token_settings: this.$routing.generate('token_settings', {tokenName: this.tokenName}),
                logout: this.$routing.generate('fos_user_security_logout'),
            },
            logOutFormId: LOGOUT_FORM_ID,
        };
    },
    computed: {
        isProfileRoute: function() {
            return this.route && this.route.startsWith('profile');
        },
        isSettingsRoute: function() {
            const routes = ['settings', 'two_factor_auth'];

            return this.route && routes.includes(this.route);
        },
        linkClasses: function() {
            return {
                wallet: this.generateRouteLinkClass('wallet' === this.route),
                feed: this.generateRouteLinkClass('show_user_feed' === this.route),
                voting: this.generateRouteLinkClass('voting' === this.route),
                referral_program: this.generateRouteLinkClass('referral-program' === this.route),
                profile: this.generateRouteLinkClass(this.isProfileRoute),
                settings: this.generateRouteLinkClass(this.isSettingsRoute),
                token_settings: this.generateRouteLinkClass(false),
                logout: this.generateRouteLinkClass(false),
            };
        },
    },
    methods: {
        generateRouteLinkClass: function(isActive) {
            return [this.linkClass, isActive ? 'active' : ''];
        },
    },
};
</script>
