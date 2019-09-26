import LimitedTextarea from './components/LimitedTextarea.vue';
import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
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
        country: '',
    },
    watch: {
        country: function() {
            if ('' === this.country) {
                this.$refs.zipCode.value = '';
            }
            this.$refs.zipCode.disabled = '' === this.country;
        },
    },
    mounted: function() {
        this.firstName = this.$refs.firstName.getAttribute('value');
        this.lastName = this.$refs.lastName.getAttribute('value');
        this.city = this.$refs.city.getAttribute('value');
        this.country = this.$refs.savedCountry.value;
        this.showEditForm = this.$refs.editFormShowFirst.value;
    },
    components: {
        BbcodeEditor,
        BbcodeHelp,
        BbcodeView,
        LimitedTextarea,
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
