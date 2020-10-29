import VueI18n from 'vue-i18n';
import CustomFormatter from './custom-formatter';

const i18n = new VueI18n({
    locale: 'locale',
    formatter: new CustomFormatter(),
    messages: {
        'locale': window.translations,
    },
});

export default i18n;
