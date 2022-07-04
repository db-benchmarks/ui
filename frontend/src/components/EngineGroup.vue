/* Copyright (C) 2022 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/
<template>
  <div id="eg-accordion">
    <div class="row mt-4">
      <template v-for="(value, name) in groups">
        <div class="col-2 d-flex justify-content-center" :key="name"
             data-toggle="collapse" :data-target="'#collapse-'+name" role="button" aria-expanded="false"
             :aria-controls="'collapse-'+name">
          <div @click="checkChildIsSingle(name, value)"
               :class="'d-flex engine-block justify-content-center'+ (isAnyActive(name) ? ' active-engine' : '')">
            <img :src="require(`@/assets/logos/${name}.svg`)">
            <span class="engine-badge">{{ getActiveItemsCount(value) }}</span>
          </div>

        </div>
      </template>
    </div>

    <template v-for="(value, name) in groups">
      <div :key="name" class="row mt-4 collapse multi-collapse" :id="'collapse-'+name"
           :aria-labelledby="'collapse-'+name" data-parent="#eg-accordion">
        <div class="col">
          <ButtonGroup v-bind:items="filterItems(name)"
                       v-bind:switch="false"
                       v-bind:capitalize="false"
                       v-bind:active-items="activeItems"
                       v-on:changed="changed()"/>

        </div>
      </div>
    </template>

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
    },
    checkChildIsSingle(groupName, blockItems) {
      let items = this.filterItems(groupName);
      let itemKeys = Object.keys(items);

      if (itemKeys.length === 1) {
        items[itemKeys[0]][Object.keys(blockItems)[0]] = !items[itemKeys[0]][Object.keys(blockItems)[0]]
        this.$emit('changed')
      }
    },
    filterItems(groupName) {
      const asArray = Object.entries(this.items);

      // eslint-disable-next-line no-unused-vars
      const filtered = asArray.filter(([key, val]) => {
        let fullName = Object.keys(val)[0];
        return fullName.indexOf(groupName) === 0 && this.activeItems[fullName] === 1
      });

      return Object.fromEntries(filtered);
    },
    getActiveItemsCount(group) {
      return Object.values(group).filter((value => value === true)).length + '/' + Object.values(group).length;
    },
    isAnyActive(name) {

      let result = false;
      Object.values(this.groups[name]).map(function (value) {
        if (value) {
          result = true;
        }
      })

      return result;
    }
  }
}
</script>

<style scoped>

img {
  width: 65%;
  padding: 10px;
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

.engine-badge {
  position: absolute;
  bottom: 10px;
  right: 20px;
  border: 2px solid;
  border-radius: 15px;
  padding: 0 6px 0 6px;
  background-color: white;
  font-size: small;
  font-weight: bold;
}

.engine-block {
  width: 80%;
}

div[aria-expanded="true"] {
  box-shadow: 0 0 10px #cfcfcf;
  border-radius: 15px;
  width: 100%;
}

.collapsing {
  -webkit-transition: none;
  transition: none;
  display: none;
}
</style>
