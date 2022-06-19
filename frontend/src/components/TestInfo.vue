/* Copyright (C) 2022 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/
<template>
  <div class="test-info">
    <p v-html="getTestInfo"></p>
    <p><b>Server:</b> {{ shortServerInfo }}</p>
    <ModalLargeScroll>
      <template v-slot:trigger>
        <a class="links" data-toggle="modal" data-target="#modal-long">Show more</a>
      </template>
      <template v-slot:content>
        <Tabs v-bind:items="fullServerInfo"></Tabs>
      </template>
    </ModalLargeScroll>
  </div>
</template>

<script>
import ModalLargeScroll from "@/components/ModalLargeScroll";
import Tabs from "@/components/Tabs";
export default {
  name: "TestInfo",
  components: {ModalLargeScroll, Tabs},
  props: {
    testInfo: {
      required: true
    },
    shortServerInfo: {
      required: true
    },
    fullServerInfo: {
      required: true
    },
  },
  computed: {
    getTestInfo() {
      if (this.testInfo !== undefined) {
        return '<b>Test:</b> ' + this.testInfo.replaceAll('* ', '<br>&nbsp;&nbsp;&nbsp;&nbsp;* ')
      }
      return "";
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

.test-info p{
  margin-bottom: 0;
}
</style>
