import PersonalData from '../components/PersonalData';
import AccountSettings from '../components/AccountSettings';
import TokenData from '../components/TokenData';
new Vue({
    el: '#profile',
    data: {
        tabSelected: 'personalData',
    },
    components: {
        PersonalData,
        AccountSettings,
        TokenData,
    },
});
