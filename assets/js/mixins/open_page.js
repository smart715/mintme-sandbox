import {TARGET_VALUES} from '../utils/constants';

export default {
    methods: {
        goToPage: function(link, target = '_self') {
            if (!TARGET_VALUES.includes(target)) {
                return false;
            }

            window.open(link, target);

            return true;
        },
    },
};
