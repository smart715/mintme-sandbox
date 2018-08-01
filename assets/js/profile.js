import PersonalDataForm from '../components/PersonalDataForm';
import AccountSettings from '../components/AccountSettings';
import Tabs from 'bootstrap-vue/es/components';

Vue.use(Tabs);

new Vue({
    el: '#profile',
    components: {
        PersonalDataForm,
        AccountSettings,
    },
});
