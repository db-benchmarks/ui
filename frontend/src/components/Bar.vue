/* Copyright (C) 2022 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */
<template>
  <div class="container mt-3">
    <template v-for="(row, index) in rows">

      <div :key="rows[row]" class="row"
           v-bind:class="[checkGroupingOffcet(index) ?'mb-2' : '' ]">
        <div class="col-4">
          <span class="bar-name">{{ names[index - 1] }}</span></div>
        <div class="col-8">
          <div class="progress mt-1">
            <div class="progress-bar progress-bar-striped"
                 role="progressbar"
                 v-bind:style="{'width': row.percent+'%'}"
                 :aria-valuenow="row.percent"
                 aria-valuemin="0"
                 aria-valuemax="100">
              {{ row.value }}x
            </div>
          </div>
        </div>
      </div>

    </template>

  </div>
</template>

<script>
export default {
  props: {
    rows: {
      type: Object,
      required: true
    },
    names: {
      type: Array,
      required: true
    },
    cache: {
      type: Array,
      required: true
    },
    groupedCount: {
      type: Number,
      required: true
    }
  },
  methods: {
    checkGroupingOffcet(row) {
      if (this.groupedCount > 1) {
        if (row % this.groupedCount == 0) {
          return true;
        }
        return false
      }
      return false;
    }
  }
}
</script>

<style scoped>
.bar-name {
  font-weight: bold;
  font-size: small;
}
</style>
