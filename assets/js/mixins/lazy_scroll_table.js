export default {
    data() {
        return {
            tableData: null,
            loading: false,
            _scrollListenerStarted: false,
        };
    },
    computed: {
        showDownArrow: function() {
            return (Array.isArray(this.tableData) && this.tableData.length > 7);
        },
    },
    methods: {
        updateTableData: function(attach = false) {},
        startScrollListening: function() {
            const table = this.$refs.table;

            if (typeof table === 'undefined') {
                return;
            }

            let tableEl = this.$refs.table.$el;
            let tbodyEl;

            if (typeof tableEl !== 'undefined') {
                tbodyEl = tableEl.tBodies[0];
            } else {
                tbodyEl = this.$refs.table;
            }

            tbodyEl.onscroll = (evt) => {
                let boundings = evt.target.getBoundingClientRect();

                if (evt.target.scrollTop && evt.target.scrollTop + boundings.height >=
                    evt.target.scrollHeight - 1 && !this.loading) {
                    this.loading = true;
                    this.updateTableData(true).then(() => this.loading = false);
                }
            };
        },
        scrollDown: function() {
            let parentDiv = this.$refs.table.$el.tBodies[0];
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
        startScrollListeningOnce: function(val) {
            if (!this._scrollListenerStarted && Array.isArray(val) && val.length) {
                // Hack to execute code when table actually appears
                // TODO: get rid of this
                setTimeout(this.startScrollListening, 500);
                this._scrollListenerStarted = true;
            }
        },
    },
    watch: {
        tableData: {
            handler(val) {
                this.startScrollListeningOnce(val);
            },
            deep: true,
        },
    },
};
