<script>
  export default {
    name: 'pageloadspinner',
    props: {
      spinnerDiv: {type: String, required: true},
    },
    render() {
      return '<div></div>';
    },
    data: function() {
        return {
              requests: 1,
              visible: true,
            };
    },
    methods: {
      hide: function() {
        this.requiests--;
        if (this.visible && !this.requests) {
            document.getElementById(this.spinnerDiv).classList.add('hidden');
            this.visible = false;
          }
        },
      show: function() {
        this.requests++;
        if (!this.visible && this.requests == 1) {
            document.getElementById(this.spinnerDiv).classList.remove('hidden');
            this.visible = true;
          }
        },
    },
  };
</script>
