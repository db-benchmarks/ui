<template>
  <svg v-on:click="showDiff" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px"
       y="0px"
       width="1024px" height="1024px" viewBox="0 0 1024 1024"
       style="enable-background:new 0 0 1024 1024;"
       xml:space="preserve">
    <g>
    <path fill="currentColor"
          d="M448 256H320v128H192v128h128v128h128V512h128V384H448V256zM192 896h384V768H192V896zM640 0H128v64h480l224 224v608h64V256L640 0zM0 128v896h768V320L576 128H0zM704 960H64V192h480l160 160V960z"/>
    </g>
  </svg>
</template>
<script>

export default {
  name: "DiffIcon",
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
svg {
  width: 15px;
  height: 15px;
  color: black;
  cursor: pointer;
}

.red {
  color: #e10000;
}

.green {
  color: #ffd062;
}
</style>
