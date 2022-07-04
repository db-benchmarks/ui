<template>
  <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px"
       y="0px" v-on:click="showInfo"
       v-bind:class="classObject"
       width="438.533px" height="438.533px" viewBox="0 0 438.533 438.533"
       style="enable-background:new 0 0 438.533 438.533;"
       v-tooltip="'This query\'s result is different from the others of different colors. Leave two databases to inspect the difference further'"
       xml:space="preserve"><g><path fill="currentColor" d="M409.133,109.203c-19.608-33.592-46.205-60.189-79.798-79.796C295.736,9.801,259.058,0,219.273,0
		c-39.781,0-76.47,9.801-110.063,29.407c-33.595,19.604-60.192,46.201-79.8,79.796C9.801,142.8,0,179.489,0,219.267
		c0,39.78,9.804,76.463,29.407,110.062c19.607,33.592,46.204,60.189,79.799,79.798c33.597,19.605,70.283,29.407,110.063,29.407
		s76.47-9.802,110.065-29.407c33.593-19.602,60.189-46.206,79.795-79.798c19.603-33.596,29.403-70.284,29.403-110.062
		C438.533,179.485,428.732,142.795,409.133,109.203z M182.727,54.813c0-2.666,0.855-4.853,2.57-6.565
		c1.712-1.711,3.903-2.57,6.567-2.57h54.82c2.662,0,4.853,0.859,6.561,2.57c1.711,1.712,2.573,3.899,2.573,6.565v45.682
		c0,2.664-0.862,4.854-2.573,6.564c-1.708,1.712-3.898,2.568-6.561,2.568h-54.82c-2.664,0-4.854-0.856-6.567-2.568
		c-1.715-1.709-2.57-3.9-2.57-6.564V54.813z M292.359,356.309c0,2.662-0.863,4.853-2.57,6.561c-1.704,1.714-3.895,2.57-6.563,2.57
		H155.317c-2.667,0-4.854-0.856-6.567-2.57c-1.712-1.708-2.568-3.898-2.568-6.564v-45.682c0-2.67,0.856-4.853,2.568-6.567
		c1.713-1.708,3.903-2.57,6.567-2.57h27.41v-91.358h-27.41c-2.667,0-4.853-0.855-6.567-2.568c-1.712-1.711-2.568-3.901-2.568-6.567
		v-45.679c0-2.666,0.856-4.853,2.568-6.567c1.715-1.713,3.905-2.568,6.567-2.568h91.367c2.662,0,4.853,0.855,6.561,2.568
		c1.711,1.714,2.573,3.901,2.573,6.567v146.179h27.401c2.669,0,4.859,0.855,6.57,2.566c1.704,1.712,2.566,3.901,2.566,6.567v45.683
		H292.359z"/></g>
    </svg>
</template>
<script>


export default {
  name: "InfoIcon",
  props: {
    row: {
      required: true
    },
    id: {
      required: true
    },
    hasDiff: {
      required: true
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

svg {
  width: 20px;
  height: 20px;
  cursor: pointer;
  box-shadow: 2px 2px 0 black;
  border-radius: 10px;
}

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
</style>
