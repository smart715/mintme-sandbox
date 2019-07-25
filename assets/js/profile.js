import LimitedTextarea from './components/LimitedTextarea.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import markitupSet from './markitup.js';
import markitup from 'markitup';
import {minLength, helpers} from 'vuelidate/lib/validators';
const xRegExp = require('xregexp');
const names = helpers.regex('names', xRegExp('^[\\p{L}]+[\\p{L}\\s\'‘’`´-]*$', 'u'));

new Vue({
    el: '#profile',
    data: {
        showEditForm: false,
        firstName: '',
        lastName: '',
        city: '',
    },
    mounted: function() {
        this.firstName = this.$refs.firstName.getAttribute('value');
        this.lastName = this.$refs.lastName.getAttribute('value');
        this.city = this.$refs.city.getAttribute('value');
        this.showEditForm = this.$refs.editFormShowFirst.value;
    },
    components: {
        LimitedTextarea,
        BbcodeView,
        BbcodeHelp,
    },
    validations: {
        firstName: {
            helpers: names,
            minLength: minLength(2),
        },
        lastName: {
            helpers: names,
            minLength: minLength(2),
        },
        city: {
            helpers: names,
            minLength: minLength(2),
        },
    },
});

markitup('textarea', markitupSet);
