<template>
  <font-awesome-icon :class="'float-right cursor ' + color" icon="fa-solid fa-circle-info" v-on:click="showDiff"/>
</template>
<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'

export default {
  name: "DiffIcon",
  components: {FontAwesomeIcon},
  props: {
    mismatch: {
      required: false
    },
    row: {
      required: true
    }
  },
  computed: {
    color: function () {
      if (this.mismatch === false) {
        return "green";
      }
      return "red";
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
