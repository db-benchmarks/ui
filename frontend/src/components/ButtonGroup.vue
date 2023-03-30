/* Copyright (C) 2023 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */
<template>
  <div>
    <template v-for="record in items">
      <button
          v-for="(selected, name) in record" :key="name" type="button"
          v-bind:class="[{ 'btn-success': selected }, 'btn-secondary']"
          class="btn btn-sm" @click="click(record)">
        {{
          (capitalize
              ? name.replaceAll("_", " ").capitalize().replaceAll(/(manticore)_(master|columnar)_(.*?)_(.*?)/g, 'Manticore $2 $3 $4')
              : name.replaceAll(/(manticore)_(master|columnar)_(.*?)_(.*?)/g, 'Manticore $2 $3 $4'))
        }} {{ append }}
      </button>
    </template>
  </div>
</template>

<script>
export default {
  props: {
    items: {
      required: true
    },
    switch: {
      type: Boolean,
      required: true
    },
    append: {
      type: String,
      required: false
    },
    capitalize: {
      type: Boolean,
      required: true
    },
  },
  methods: {
    click(item) {
      if (this.switch) {
        this.clearItems();
      }
      for (let name in item) {
        item[name] = !item[name]
      }

      this.$emit('changed');
    },
    clearItems() {
      for (let item in this.items) {
        for (let row in this.items[item]) {
          this.items[item][row] = 0
        }
      }
    }
  }
}
</script>

<style scoped>

button {
  margin-right: 3px;
  margin-bottom: 3px;
  border-width: 2px;
}

.btn-group.special {
  display: flex;
  position: relative;
  /*top: -45px;*/
}

.special .btn {
  word-break: break-all;
  flex: 1
}

.btn:focus {
  outline: none;
  box-shadow: none;
}

.btn:active {
  background-color: unset;
}
</style>
