<script>
  export default {
    name: 'pageloadspinner',
    props: {
      spinnerDiv: {type: String, required: true},
    },
    render() {
      return '<div></div>';
    },
    methods: {
      hide: function() {
        document.getElementById(this.spinnerDiv).classList.add('hidden');
      },
    },
  };
</script>
