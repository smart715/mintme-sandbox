
export default {
    props: {
        loggedIn: Boolean,
    },
    computed: {
        marketPricePositionClass: function() {
            return this.loggedIn ? 'text-sm-right text-xl-right' : 'text-xl-left';
        },
    },
};
