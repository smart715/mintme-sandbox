<template>
    <li class="nav-item d-flex justify-content-center align-items-center">
        <div
            class="dropdown lang-dropdown"
            :class="{ 'show': showLangMenu }"
            v-on-clickaway="hideLangMenu"
        >
            <button
                class="btn btn-lang-menu dropdown-toggle"
                type="button"
                aria-haspopup="true"
                :aria-expanded="showLangMenu"
                @click="showLangMenu = true"
            >
                <span :class="currentLocaleClass"></span>
            </button>
            <div
                class="dropdown-menu lang-menu"
                :class="{ 'show': showLangMenu }"
                aria-labelledby="dropdownLangMenuButton"
            >
                <a class="dropdown-item" @click="changeLocale('en')">
                    <span class="flag-icon flag-icon-gb"></span> English
                </a>
                <a class="dropdown-item" @click="changeLocale('es')">
                    <span class="flag-icon flag-icon-es"></span> Español
                </a>
                <a class="dropdown-item" @click="changeLocale('ar')">
                    <span class="flag-icon flag-icon-ar"></span> العربية
                </a>
                <a class="dropdown-item" @click="changeLocale('ru')">
                    <span class="flag-icon flag-icon-ru"></span> Русский
                </a>
                <a class="dropdown-item" @click="changeLocale('pt')">
                    <span class="flag-icon flag-icon-pt"></span> Portugues
                </a>
                <a class="dropdown-item" @click="changeLocale('fr')">
                    <span class="flag-icon flag-icon-fr"></span> Française
                </a>
                <a class="dropdown-item" @click="changeLocale('pl')">
                    <span class="flag-icon flag-icon-pl"></span> Polski
                </a>
                <a class="dropdown-item" @click="changeLocale('uk')">
                    <span class="flag-icon flag-icon-uk"></span> Українська
                </a>
            </div>
        </div>
    </li>
</template>

<script>
import {directive as onClickaway} from 'vue-clickaway';
import {HTTP_ACCEPTED} from '../utils/constants';

export default {
    name: 'LocaleSwitcher',
    directives: {
        onClickaway,
    },
    props: {
        currentLocale: String,
    },
    data() {
        return {
            currentLocaleClass: 'flag-icon flag-icon-' + this.currentLocale,
            showLangMenu: false,
            engLocale: 'en',
        };
    },
    computed: {
        userLocale: function() {
            return 'uk' === this.currentLocale ? 'ua' : this.currentLocale;
        },
    },
    methods: {
        changeLocale: function(locale) {
            this.$axios.single.post(this.$routing.generate('change_locale', {
                locale,
            }))
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        let hrefWithLocale = '';
                        let href = location.href;

                        if (this.engLocale === this.userLocale && this.engLocale !== locale ) {
                            let origin = location.origin;
                            hrefWithLocale = href.replace(origin, origin+'/'+locale);
                        } else if (this.engLocale !== this.userLocale && this.engLocale === locale) {
                            hrefWithLocale = href.replace(this.userLocale + '/', '');
                        } else {
                            hrefWithLocale = href.replace(this.userLocale, locale);
                        }

                        window.location.href = hrefWithLocale;
                        // location.reload();
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
