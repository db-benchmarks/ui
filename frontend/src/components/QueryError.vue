<template>
  <span class="error-emoji"
        v-tooltip="message">{{ getEmoji() }}</span>
</template>
<script>


export default {
  name: "InfoIcon",
  props: {
    type: {
      required: true
    },
    message: {
      required: true
    }
  },
  methods: {
    getEmoji() {
      if (this.type === 'error' || this.type === 'timeout') {
        return "⚠️";
      }
      return "❌"
    }
  }
}
</script>

<style>
.error-emoji {
  text-shadow: 1px 1px 2px black;
  cursor: pointer;
}
</style>