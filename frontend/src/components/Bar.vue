/* Copyright (C) 2022 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */
<template>
  <div class="align-items-start m-3">
    <template v-for="(row, index) in sortRows">

      <div :key="sortRows[row]" class="row"
           v-bind:class="[checkGroupingOffset(index+1) ? 'mb-2' : '' ]">
        <div class="col-4">
          <span class="bar-name">{{ row.engine }}</span></div>
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
    groupOffset: {
      type: Number,
      required: true
    }
  },
  methods: {
    checkGroupingOffset(row) {
      if (this.groupOffset > 1) {
        return row % (Object.keys(this.rows).length / this.groupOffset) === 0;
      }
      return false;
    },

  },
  computed:{
    sortRows: function (){
      return Object.values(this.rows)
          .sort(function (a, b) {

            a.value = parseFloat(a.value);
            if (a.type < b.type) return -1;
            if (a.type > b.type) return 1;
            if (a.value < b.value) return -1;
            if (a.value > b.value) return 1;

            return 0
          })
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
