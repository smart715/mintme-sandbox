<template>
    <div
        class="dropup language-switcher d-inline"
        :class="{ 'show': showLangMenu }"
        v-on-clickaway="hideLangMenu"
    >
        <button
            class="btn dropdown-toggle"
            type="button"
            aria-haspopup="true"
            :aria-expanded="showLangMenu"
            @click="showLangMenu = true"
        >
            <span :class="currentLocaleClass"></span> {{ currentLocaleLabel }}
        </button>
        <div
            class="dropdown-menu lang-menu"
            :class="{ 'show': showLangMenu }"
            aria-labelledby="dropdownLangMenuButton"
        >
            <a v-for="locale in flagsWithLocales" v-bind:key="locale.flag" class="dropdown-item"
               @click="changeLocale(locale.locale)">
                <span :class="'flag-icon flag-icon-'+locale.flag"></span> {{ locale.label }}
            </a>
        </div>
    </div>
</template>

<script>
import {directive as onClickaway} from 'vue-clickaway';
import {HTTP_OK} from '../utils/constants';

export default {
    name: 'LocaleSwitcher',
    directives: {
        onClickaway,
    },
    props: {
        currentLocale: String,
        flags: String,
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
            let locales = Object.keys(this.flagNames);

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
                        let href = location.href;

                        if (this.engLocale === this.currentLocale && this.engLocale !== locale ) {
                            let origin = location.origin;
                            hrefWithLocale = href.replace(origin, origin+'/'+locale);
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
    },
};
</script>
