/* Copyright (C) 2022 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */
<template>
  <svg
      v-tooltip="'<b>Query results checksum</b><br><small>Different colors show different results. No circles or circles with the same color mean identical results.</small>'"
      xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" :class="cls"
      class="bi bi-circle-fill" viewBox="-1 -1 16 16">
    <circle cx="7" cy="7" r="7" :stroke="strokeColor" stroke-width="1"></circle>
  </svg>
</template>

<script>

export default {
  name: "HashCircle",
  props: {
    cls: {
      type: String,
      required: true
    },
    strokeColor: {
      type: String,
      required: true
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

</style>
