<script>
  export default {
    name: 'pageloadspinner',
    props: {
      spinnerDiv: {type: String, required: true},
    },
    render() {
      return '';
    },
    methods: {
      hide: function() {
        document.getElementById(this.spinnerDiv).classList.add('hidden');
      },
      show: function() {
        if (document.getElementById(this.spinnerDiv).classList.contains('hidden')) {
            document.getElementById(this.spinnerDiv).classList.remove('hidden');
        }
      },
    },
  };
</script>
