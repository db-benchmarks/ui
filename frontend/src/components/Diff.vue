<template>
  <div v-html="prettyHtml" />
</template>

<script>
import * as Diff2Html from 'diff2html';
import 'diff2html/bundles/css/diff2html.min.css';

export default {
  props:{
    diffs:{
      required: true
    }
  },
  computed: {
    prettyHtml() {
      return Diff2Html.html(this.diffs, {
        drawFileList: false,
        fileListToggle: false,
        fileListStartVisible: false,
        fileContentToggle: false,
        matching: 'lines',
        outputFormat: 'side-by-side',
        synchronisedScroll: true,
        highlight: true,
        renderNothingWhenEmpty: false,
        rawTemplates: {
          "tag-file-changed": "",
          "tag-file-renamed": "",
        }
      });
    },
  },
};
</script>

<style scoped>

</style>
