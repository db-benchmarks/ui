/* Copyright (C) 2022 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */
<template>
  <span class="query-name" @click="updateIsChecked">
    <input
        :checked="checked"
        @select="updateIsChecked"
        type="checkbox"> {{ this.query }}
  </span>
</template>

<script>
export default {
  name: "QuerySelector",
  props: {
    query: {
      type: String,
      required: true
    },
    checked: {
      required: true
    }
  },
  methods: {
    updateIsChecked() {
      this.$emit("update:checked", !this.checked);
    }
  }
}
</script>

<style scoped>
.query-name {
  cursor: pointer;
}
</style>
