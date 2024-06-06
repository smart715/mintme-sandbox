const ONE_SECOND_MS = 1000;

export default {
    data() {
        return {
            counters: {},
            timerSeconds: 0,
            timerTimeout: null,
        };
    },
    watch: {
        timerSeconds() {
            Object.keys(this.counters).map(function(key) {
                if (0 === this.counters[key]) {
                    this[key] = false;
                    this.$delete(this.counters, key);

                    return;
                }

                this.counters[key]--;
            }.bind(this));

            if (this.timerTimeout) {
                clearTimeout(this.timerTimeout);
            }
            if (0 === this.timerSeconds) {
                return;
            }

            this.timerTimeout = setTimeout(() => {
                if (this.timerTimeout) {
                    clearTimeout(this.timerTimeout);
                }

                this.timerSeconds--;
            }, ONE_SECOND_MS);
        },
    },
    methods: {
        isTimerActive(name) {
            return !!this.counters[name];
        },
        getTimerSeconds(name) {
            return this.counters[name] || 0;
        },
        startTimer(name, ms) {
            this[name] = true;
            this.$set(this.counters, name, ms + 1);
            this.timerSeconds = ms;
        },
    },
};
