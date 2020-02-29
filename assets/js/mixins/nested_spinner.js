export default {
  data() {
    return {
      spinnerQuantity: 0,
    };
  },
  methods: {
    showSpinner: function() {
      if (!this.spinnerQuantity) {
        this.$refs.spinner.show();
      }
      this.spinnerQuantity = this.spinnerQuantity + 1;
    },
    hideSpinner: function() {
      this.spinnerQuantity = this.spinnerQuantity - 1;
      if (!this.spinnerQuantity) {
        this.$refs.spinner.hide();
      }
    },
  },
};
