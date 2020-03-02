import {mapMutations, mapGetters} from 'vuex';

export default {
  computed: {
    ...mapGetters('pageSpinnerStorage', {
      getIsSpinnerVisible: 'getIsSpinnerVisible',
    }),
  },
  methods: {
    ...mapMutations('pageSpinnerStorage', [
      'directShowSpinner',
      'showSpinner',
      'directHideSpinner',
      'hideSpinner',
    ]),
  },
};
