export default {
    data() {
        return {
            tableData: null,
            loading: false,
            scrollListenerStarted: false,
            scrollListenerAutoStart: true,
            perPage: 20,
        };
    },
    computed: {
        showSeeMoreButton: function() {
            return !this.scrollListenerStarted && this.tableData.length >= this.perPage;
        },
    },
    methods: {
        updateTableData: function(attach = false) {},
        startScrollListening: function() {
            const table = this.$refs.table;

            if ('undefined' === typeof table) {
                return;
            }

            const tableEl = this.$refs.table.$el;
            let tbodyEl;

            if ('undefined' !== typeof tableEl) {
                tbodyEl = tableEl.tBodies[0];
            } else {
                tbodyEl = this.$refs.table;
            }

            tbodyEl.onscroll = (evt) => {
                const boundings = evt.target.getBoundingClientRect();

                if (evt.target.scrollTop && evt.target.scrollTop + boundings.height >=
                    evt.target.scrollHeight - 1 && !this.loading) {
                    this.loading = true;
                    this.updateTableData(true).then(() => this.loading = false);
                }
            };
        },
        scrollDown: function() {
            const parentDiv = this.$refs.table.$el.tBodies[0];
            parentDiv.scrollTop = parentDiv.scrollHeight;
            const parentDivFirefox = this.$refs.table.$el.parentElement;
            parentDivFirefox.scrollTop = parentDivFirefox.scrollHeight;
        },
        startScrollListeningOnce: function(val) {
            if (!this.scrollListenerStarted && Array.isArray(val) && val.length) {
                // Hack to execute code when table actually appears
                // TODO: get rid of this
                setTimeout(this.startScrollListening, 500);
                this.scrollListenerStarted = true;
            }
        },
    },
    watch: {
        tableData: {
            handler(val) {
                if (this.scrollListenerAutoStart) {
                    this.startScrollListeningOnce(val);
                }
            },
            deep: true,
        },
    },
};
