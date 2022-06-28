<template>
  <font-awesome-icon v-bind:class="classObject" icon="fa-solid fa-circle-info" v-on:click="showInfo"/>
</template>
<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'

export default {
  name: "InfoIcon",
  components: {FontAwesomeIcon},
  props: {
    row: {
      required: true
    },
    id: {
      required: true
    },
    hasDiff: {
      required: true
    },
    strokeColor: {
      type: String,
      required: false
    }
  },
  computed: {
    classObject: function () {
      let classes = ['cursor'];
      if (this.hasDiff) {
        classes.push(this.class);
        classes.push('stroke-' + this.strokeColor)
      }

      return classes;
    }
  },
  methods: {
    showInfo: function () {
      this.$emit("showInfo", this.row, this.id);
    }
  }
}
</script>

<style scoped>

.orange {
  color: orange;
}

.yellow {
  color: yellow;
}

.red {
  color: lightcoral;
}

.blue {
  color: blue;
}

.green {
  color: green;
}

.purple {
  color: purple;
}

.brown {
  color: brown;
}

.black {
  color: black;
}

.white {
  color: white;
}

.stroke-black{
  border: 2px solid black;
  border-radius: 10px;
}

.stroke-white{
  border: 2px solid white;
  border-radius: 10px;
}
</style>
