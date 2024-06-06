<template>
    <div
        class="dropdown language-switcher d-inline"
        :class="{ 'show': showLangMenu }"
        v-on-clickaway="hideLangMenu"
    >
        <button
            class="btn btn-link dropdown-toggle"
            type="button"
            aria-haspopup="true"
            :aria-expanded="showLangMenu"
            @click="toggleMenu"
        >
            <span v-if="showFlagInSelect" :class="currentLocaleClass"></span> {{ currentLocaleLabel }}
        </button>
        <div
            class="dropdown-menu lang-menu lg-hide"
            :class="{ 'show': showLangMenu, 'dropdown-menu-right': hideElements }"
            aria-labelledby="dropdownLangMenuButton"
        >
            <a v-for="locale in flagsWithLocales" v-bind:key="locale.flag" class="dropdown-item"
               @click="changeLocale(locale.locale)">
                <span :class="'flag-icon mr-2 flag-icon-'+locale.flag"></span> {{ locale.label }}
            </a>
        </div>
    </div>
</template>

<script>
import {directive as onClickaway} from 'vue-clickaway';
import {HTTP_OK, ScreenMediaSize} from '../utils/constants';
import {getScreenMediaSize} from '../utils';

export const SWITCH_MODE = {
    click: 'click',
    hover: 'hover',
};

export default {
    name: 'LocaleSwitcher',
    directives: {
        onClickaway,
    },
    props: {
        currentLocale: String,
        flags: String,
        mode: {
            type: String,
            default: SWITCH_MODE.click,
        },
        showFlagInSelect: {
            type: Boolean,
            default: true,
        },
        hideElements: Boolean,
    },
    data() {
        return {
            showLangMenu: false,
            engLocale: 'en',
        };
    },
    computed: {
        flagNames: function() {
            return JSON.parse(this.flags);
        },
        flagsWithLocales: function() {
            const locales = Object.keys(this.flagNames);

            return locales.map((loc) => {
                return {
                    locale: loc,
                    flag: this.flagNames[loc].flag,
                    label: this.flagNames[loc].label,
                };
            });
        },
        currentLocaleClass: function() {
            return 'flag-icon flag-icon-' + this.flagNames[this.currentLocale].flag;
        },
        currentLocaleLabel: function() {
            return this.flagNames[this.currentLocale].label;
        },
    },
    methods: {
        changeLocale: function(locale) {
            this.$axios.single.post(this.$routing.generate('change_locale', {
                locale,
            }))
                .then((response) => {
                    if (response.status === HTTP_OK) {
                        let hrefWithLocale = '';
                        const href = location.href;

                        if (this.engLocale === this.currentLocale && this.engLocale !== locale ) {
                            const origin = location.origin;
                            hrefWithLocale = href.replace(origin, `${origin}/${locale}`);
                        } else if (this.engLocale !== this.currentLocale && this.engLocale === locale) {
                            hrefWithLocale = href.replace(this.currentLocale + '/', '');
                        } else {
                            hrefWithLocale = href.replace(this.currentLocale, locale);
                        }

                        window.location.href = hrefWithLocale;
                    } else {
                        this.$toasted.error(this.$t('toasted.error.try_later'));
                    }
                }, (error) => {
                    this.$toasted.error(this.$t('toasted.error.try_later'));
                });
        },
        hideLangMenu: function() {
            this.showLangMenu = false;
        },
        toggleMenu: function() {
            if (this.mode === SWITCH_MODE.hover && getScreenMediaSize() > ScreenMediaSize.MD) {
                return;
            }

            this.showLangMenu = !this.showLangMenu;
            this.showCoinDropdown = false;
        },
    },
};
</script>
