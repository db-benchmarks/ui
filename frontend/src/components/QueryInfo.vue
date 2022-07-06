<template>
  <ModalLargeScroll modal-id="modal-query-info">
    <template v-slot:header>
      <div>
        <h4>Query execution info</h4>
        <p>Query: <span class="p-content">{{ getQuery() }}</span></p>
        <p>Database: <span class="p-content">{{ engineInQueryInfo }}</span></p>
      </div>
    </template>
    <template v-slot:content>
      <Tabs v-bind:items="tabsContent"></Tabs>
    </template>
  </ModalLargeScroll>
</template>

<script>
import ModalLargeScroll from "@/components/ModalLargeScroll";
import Tabs from "@/components/Tabs";

export default {
  name: "QueryInfo",
  components: {ModalLargeScroll, Tabs},
  props: {
    tabsContent: {
      required: true
    },
    queryInfo: {
      required: true
    },
    engineInQueryInfo: {
      required: true
    }
  },
  methods: {
    getQuery() {
      if (this.queryInfo.Query !== undefined) {
        return this.queryInfo['Query']['Original query']
      }
    }
  }
}
</script>

<style scoped>
p {
  margin-bottom: 0;
}

.p-content {
  font-weight: bold;
  font-size: small;
}
</style>
