/* Copyright (C) 2022 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/
<template>
  <div>
    <p class="test-info">Test: {{ testInfo }}</p>
  </div>
</template>

<script>
export default {
  name: "TestInfo",
  props: {
    testInfo: {
      required: true
    }
  }
}
</script>

<style scoped>
.test-info {
  border: 1px solid;
  border-radius: 5px;
  padding: 5px 10px;
}
</style>
