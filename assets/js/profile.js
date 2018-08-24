import ChildForm from '../components/ChildForm';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import VueTippy from 'vue-tippy';
import Toasted from 'vue-toasted';
Vue.use(VueTippy);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const profileData =
        JSON.parse(document.getElementById('initial-profile-data').value);

new Vue({
    el: '#profile',
    components: {
        FontAwesomeIcon,
        ChildForm,
    },
    data: {
        showEditForm: false,
        showDescriptionEditForm: false,
        profile: profileData,
    },
    computed: {
        lastName: function() {
            return this.profile.lastName;
        },
        firstName: function() {
            return this.profile.firstName;
        },
        city: function() {
            return this.profile.city;
        },
        country: function() {
            return this.profile.country;
        },
        description: function() {
            return this.profile.description;
        },
   },
    methods: {
        loadEditForm: function(url) {
            this.showEditForm = true;
            Vue.nextTick(() => this.$refs.formedit.loadForm(url));
        },
        loadDescriptionEditForm: function(url) {
            this.showDescriptionEditForm = true;
            Vue.nextTick(() => this.$refs.formdescriptionedit.loadForm(url));
        },
        handleFormEditSubmit: function(response) {
            this.showEditForm = false;
            this.profile = response.data.profile;
            this.$toasted.success(response.data.message);
        },
        handleFormDescriptionEditSubmit: function(response) {
            this.showDescriptionEditForm = false;
            this.profile = response.data.profile;
            this.$toasted.success(response.data.message);
        },
        handleFormEditError: function() {
            this.showEditForm = false;
            this.$toasted.error('An error has ocurred, please try again later');
        },
        handleFormDescriptionEditError: function() {
            this.showDescriptionEditForm = false;
            this.$toasted.error('An error has ocurred, please try again later');
        },
    },
});
