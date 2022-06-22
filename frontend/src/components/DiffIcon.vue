<template>
  <font-awesome-icon :class="'float-right cursor '" icon="fa-solid fa-arrow-down-up-across-line" v-on:click="showDiff"/>
</template>
<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'

export default {
  name: "DiffIcon",
  components: {FontAwesomeIcon},
  props: {
    row: {
      required: true
    }
  },
  methods: {
    showDiff: function () {
      this.$emit("showDiff", this.row);
    }
  }
}
</script>

<style scoped>
.red {
  color: #e10000;
}

.green {
  color: #ffd062;
}
</style>
