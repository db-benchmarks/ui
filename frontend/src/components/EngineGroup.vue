/* Copyright (C) 2022 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/
<template>
  <div>
    <div class="row m-2">
      <template v-for="(value, name) in groups">
        <div class="col-2" :key="name">
          <img :src="require(`@/assets/logos/${name}.svg`)">
        </div>
      </template>
    </div>
    <div>
      <ButtonGroup v-bind:items="items"
                   v-bind:switch="false"
                   v-bind:capitalize="false"
                   v-bind:active-items="activeItems"
                   v-on:changed="changed()"/>
    </div>
  </div>
</template>

<script>
import ButtonGroup from "@/components/ButtonGroup";

export default {
  components: {
    ButtonGroup
  },
  props: {
    groups: {
      required: true
    },
    items: {
      type: Array,
      required: true
    },
    activeItems: {
      type: Object,
      required: false
    }
  },
  methods: {
    changed() {
      this.$emit('changed')
    }
  }
}
</script>

<style scoped>

img {
  width: 100px;
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
