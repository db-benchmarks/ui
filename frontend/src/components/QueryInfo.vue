<template>
  <ModalLargeScroll modal-id="modal-query-info">
    <template v-slot:header>
      Query execution info
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
    }
  }
}
</script>

<style scoped>

</style>
