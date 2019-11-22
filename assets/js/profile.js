import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import LimitedTextarea from './components/LimitedTextarea.vue';
import {minLength, helpers} from 'vuelidate/lib/validators';
import {zipCodeContain} from './utils/constants.js';

const xRegExp = require('xregexp');
const names = helpers.regex('names', xRegExp('^[\\p{L}]+[\\p{L}\\s\'‘’`´-]*$', 'u'));

new Vue({
    el: '#profile',
    components: {
        BbcodeEditor,
        BbcodeHelp,
        BbcodeView,
        LimitedTextarea,
    },
    data() {
        return {
            showEditForm: false,
            firstName: '',
            lastName: '',
            city: '',
            zipCode: '',
        };
    },
    mounted: function() {
        this.firstName = this.$refs.firstName.getAttribute('value');
        this.lastName = this.$refs.lastName.getAttribute('value');
        this.city = this.$refs.city.getAttribute('value');
        this.zipCode = this.$refs.zipCode.getAttribute('value');
        this.showEditForm = this.$refs.editFormShowFirst.value;
        this.$refs.zipCode.disabled = this.notAvailZipCode;
        this.countryChanged();
    },
    methods: {
        countryChanged: function() {
            if ('' === this.$refs.country.value) {
                this.$refs.zipCode.disabled = true;
                this.zipCode = '';
            } else {
                this.$refs.zipCode.disabled = false;
            }
        },
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
        zipCode: {
            zipCodeContain,
            zipCodeWrongChars: function(zipCode) {
                if (!zipCode) {
                    return true;
                }

                return zipCode.replace(/\s/g, '').length > 0;
            },
        },
    },
});
