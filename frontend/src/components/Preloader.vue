/* Copyright (C) 2023 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/
<template>
  <div v-bind:class="['fixed-top', visible === false ? 'd-none':'']">
    <div class="alert alert-warning preloader mx-auto m-2">Loading ...</div>
  </div>
</template>

<script>
export default {
  props: {
    visible: {
      type: Boolean,
      required: true
    },
  },
}
</script>
<style>
.preloader {
    width: 120px;
    border: 1px solid #000!important;
    margin: 0px;
    padding: 0px;
    text-align: center;
}
</style>
