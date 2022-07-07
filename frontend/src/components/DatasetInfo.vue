<template>
  <ModalLargeScroll modal-id="modal-dataset-info">
    <template v-slot:header>
      <p>Info about <strong>{{ engine.replaceAll('_',' ') }}</strong> and the dataset in the database</p>
    </template>
    <template v-slot:content>
      <Tabs v-bind:items="parsedContent"></Tabs>
    </template>
  </ModalLargeScroll>
</template>

<script>
import ModalLargeScroll from "@/components/ModalLargeScroll";
import Tabs from "@/components/Tabs";

export default {
  name: "DatasetInfo",
  components: {ModalLargeScroll, Tabs},
  props: {
    engine: {
      required: true
    },
    tabsContent: {
      required: true
    }
  },
  watch: {
    tabsContent() {
      this.parsedContent = {};

      for (let tab in this.tabsContent) {
        if (this.tabsContent[tab] instanceof Object) {
          let text = '<ul>';

          for (let row in this.tabsContent[tab]) {

            let rowValue = this.tabsContent[tab][row];

            if (rowValue instanceof Object) {
              rowValue = JSON.stringify(rowValue, null, 2)
            }

            if (rowValue.length === 0) {
              continue;
            }

            if (this.validURL(rowValue)) {
              rowValue = '<a target="_blank" href="' + rowValue + '">' + rowValue + '</a>';
            }
            text += '<li><strong>' + row + ':</strong> ' + rowValue + '</li>'
          }

          text += '</ul>';
          this.parsedContent[tab] = text;
        } else {
          this.parsedContent[tab] = this.tabsContent[tab];
        }
      }

    }
  },
  data() {
    return {
      parsedContent: {}
    }
  },
  methods: {
    validURL(str) {
      var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
          '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
          '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
          '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
          '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
          '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
      return !!pattern.test(str);
    },
  }
}
</script>

<style scoped>

</style>
