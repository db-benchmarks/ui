<template>
  <ModalLargeScroll modal-id="modal-query-diff">
    <template v-slot:header>
      Difference in query results
    </template>
    <template v-slot:content>
      <div v-for="(diff, name) in diff" :key="name">
        <Diff v-bind:diffs="diff"></Diff>
      </div>
    </template>
  </ModalLargeScroll>
</template>

<script>
import ModalLargeScroll from "@/components/ModalLargeScroll";
import Diff from "@/components/Diff";

export default {
  name: "QueryDiff",
  components: {ModalLargeScroll, Diff},
  props: {
    diff: {
      required: true
    }
  }
}
</script>

<style scoped>

</style>
