export default {
  props: {
    spinnerDiv: {
      type: String,
      required: false,
      defaultValue: 'page-load-spinner',
    },
  },
  data() {
    return {
      spinnerQuantity: 0,
    };
  },
  methods: {
    directShowSpinner: function() {
      this.spinnerQuantity = 0;
      document.getElementById(this.spinnerDiv).classList.add('hidden');
    },
    directHideSpinner: function() {
      this.spinnerQuantity = 0;
      if (document.getElementById(this.spinnerDiv).classList.contains('hidden')) {
        document.getElementById(this.spinnerDiv).classList.remove('hidden');
      }
    },
    showSpinner: function() {
      if (!this.spinnerQuantity) {
        this.directShowSpinner();
      }
      this.spinnerQuantity = this.spinnerQuantity + 1;
    },
    hideSpinner: function() {
      if (this.spinnerQuantity) {
        this.spinnerQuantity = this.spinnerQuantity - 1;
        if (!this.spinnerQuantity) {
          this.directHideSpinner();
        }
      }
    },
  },
};
