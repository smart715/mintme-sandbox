<template>
    <div class="d-flex align-items-center mt-2 mb-3">
        <font-awesome-icon
            :icon="['fas', 'arrow-left']"
            class="mr-3 settings-header-icon d-none d-md-block c-pointer"
            :class="{'with-dropdown': showTokenDropdown}"
            @click="goBack"
        />
        <font-awesome-icon
            :icon="['fas', 'bars']"
            class="mr-2 settings-header-icon d-md-none c-pointer"
            :class="{'with-dropdown': showTokenDropdown}"
            @click="openSidenav"
        />
        <div class="settings-header-icon-spacer mx-2 d-md-none" :class="{'with-dropdown': showTokenDropdown}"></div>
        <div v-if="showTokenDropdown" class="d-flex align-items-end">
            <h2
                class="page-title token-settings-title mr-2 mb-0"
                v-html="$t('page.token_settings_for.title')"
            ></h2>
            <m-dropdown
                :label="$t('page.token_settings.token_label')"
                :hideAssistive="true"
                class="token-dropdown-menu"
                type="primary"
            >
                <template v-slot:button-content>
                    <div class="token-dropdown-item">
                        <coin-avatar
                            class="mr-2"
                            :image="tokenAvatar"
                            :is-user-token="true"
                        />
                        <span class="truncate-block mr-2">
                            {{ tokenName }}
                        </span>
                    </div>
                </template>
                <m-dropdown-item
                    v-for="token in otherTokens"
                    v-b-tooltip="tooltipConfig(token.name)"
                    :key="token.name"
                    :value="token.name"
                    :link="tokenSettingsLink(token.name)"
                >
                    <div class="token-dropdown-item">
                        <coin-avatar
                            class="mr-2"
                            :image="token.image.url"
                            :is-user-token="true"
                        />
                        <span class="truncate-block mr-2">
                            {{ token.name }}
                        </span>
                    </div>
                </m-dropdown-item>
            </m-dropdown>
        </div>
        <h2
            v-else
            class="page-title my-2"
            v-html="$t('page.token_settings.title')"
        ></h2>
    </div>
</template>

<script>
import {MDropdown, MDropdownItem} from '../UI';
import CoinAvatar from '../CoinAvatar';
import {VBTooltip} from 'bootstrap-vue';
import {mapGetters} from 'vuex';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faArrowLeft, faBars} from '@fortawesome/free-solid-svg-icons';

library.add(faBars, faArrowLeft);

const TOKEN_NAME_TRUNCATE_LENGTH = 20;

export default {
    name: 'TokenSettingsHeader',
    props: {
        tokenName: String,
        tokenAvatar: String,
        tokens: Array,
        tokensCount: Number,
    },
    components: {
        MDropdown,
        MDropdownItem,
        CoinAvatar,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    computed: {
        ...mapGetters('tokenSettings', [
            'getTokenName',
        ]),
        showTokenDropdown() {
            return 1 < this.tokensCount;
        },
        otherTokens() {
            return this.tokens.filter((token) => token.name !== this.tokenName);
        },
    },
    methods: {
        tokenSettingsLink(tokenName) {
            if (this.getTokenName === tokenName) {
                return;
            }

            return this.$routing.generate('token_settings', {tokenName: tokenName});
        },
        tooltipConfig: function(tokenName) {
            return {
                title: tokenName,
                placement: 'right',
                disabled: TOKEN_NAME_TRUNCATE_LENGTH > tokenName.length,
            };
        },
        openSidenav() {
            this.$emit('open-sidenav');
        },
        goBack() {
            window.location.href = this.$routing.generate('token_show_intro', {name: this.tokenName}, true);
        },
    },
};
</script>
