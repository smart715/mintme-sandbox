const tableSortPlugin = {
    install(Vue, options) {
        Vue.$sortCompare= function(a, b, key) {
            switch (this.fields[key].type) {
                case 'date':
                    return this.dateCompare(a[key], b[key]);
                case 'string':
                    return a[key].localeCompare(b[key]);
                case 'numeric':
                    return this.numericCompare(a[key], b[key]);
            }
        },
        Vue.$numericCompare= function(a, b) {
            a = parseFloat(a);
            b = parseFloat(b);

            return a < b ? -1 : (a > b ? 1 : 0);
        },
        Vue.$dateCompare= function(a, b) {
            a = moment(a, GENERAL.dateFormat).unix();
            b = moment(b, GENERAL.dateFormat).unix();

            return this.numericCompare(a, b);
        };
    },
};

export default tableSortPlugin;
