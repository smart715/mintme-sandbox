<template>

</template>
<script>
  export default {
    name: 'pageloadspinner',
    props: {
      spinnerDiv: {type: String, required: true},
    },
    mounted() {
      document.getElementById(this.spinnerDiv).classList.add('hidden');
    },
  };
</script>
