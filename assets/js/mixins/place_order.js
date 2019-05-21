export default {
    data: function() {
      return {
          showModal: false,
          modalTitle: '',
          modalSuccess: false,
      };
    },
    methods: {
        handleOrderError: function(error) {
            if (!error.status) {
                this.$toasted.error('Network Error!');
            } else {
                this.showModalAction();
            }
        },
        showModalAction: function({result, message} = {}) {
            this.modalSuccess = 1 === result;
            this.modalTitle = message ? message : (1 === result ? 'Order Created' : 'Order Failed');
            this.showModal = true;
        },
    },
};
