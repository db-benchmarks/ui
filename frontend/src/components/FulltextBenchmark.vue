/* Copyright (C) 2023 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */
<template>
  <div>
    <div class="row">
      <Table v-bind:engines="engines"
             v-bind:cache="cache"
             v-bind:results="filteredResults"
             v-bind:check-sum="checksums"
             v-bind:retestEngines="retestEngines"
             v-bind:checkedQueries="queries"
             v-on:update:checked="$emit('update:checked')"
             v-on:showDiff="$emit('showDiff', $event)"
             v-on:showInfo="forwardShowInfo"
             v-on:showDatasetInfo="$emit('showDatasetInfo', $event)"
             v-on:showRetestResults="$emit('showRetestResults', $event)"
      />
    </div>
    <div class="row mt-2" v-show="ingestingResultsVisibility">
      <h5>Data Ingestion Performance</h5>
      <p class="ingestion-disclaimer">The primary focus of these benchmarks is search performance, including query latency, concurrency, and sharding behavior. Data ingestion performance is shown for reference only. It was measured using a single loading attempt and was not subject to repeated runs or tuning, so the results should not be interpreted as a comprehensive evaluation of bulk-loading capabilities.</p>
      <IngestingTable v-bind:content="ingestingResultsFiltered"></IngestingTable>
    </div>
    <QueryInfo
        v-bind:tabsContent="parsedQueryInfo"
        v-bind:queryInfo="queryInfo"
        v-bind:engineInQueryInfo="engineInQueryInfo"
    ></QueryInfo>
    <QueryDiff v-bind:diff="diff"></QueryDiff>
    <DatasetInfo v-bind:tabs-content="datasetInfo" v-bind:engine="engineInDatasetInfo"></DatasetInfo>
  </div>
</template>

<script>
import Table from "@/components/Table";
import QueryInfo from "@/components/QueryInfo";
import QueryDiff from "@/components/QueryDiff";
import DatasetInfo from "@/components/DatasetInfo";
import IngestingTable from "@/components/IngestingTable.vue";

export default {
  name: 'FulltextBenchmark',
  components: {
    Table,
    QueryInfo,
    QueryDiff,
    DatasetInfo,
    IngestingTable
  },
  props: {
    engines: {
      type: Array,
      required: true
    },
    cache: {
      type: Array,
      required: true
    },
    filteredResults: {
      type: Array,
      required: true
    },
    checksums: {
      type: Object,
      required: true
    },
    retestEngines: {
      type: Array,
      required: true
    },
    queries: {
      type: Array,
      required: true
    },
    ingestingResultsFiltered: {
      type: Array,
      required: true
    },
    ingestingResultsVisibility: {
      type: Boolean,
      required: true
    },
    parsedQueryInfo: {
      type: Object,
      required: true
    },
    queryInfo: {
      type: Object,
      required: true
    },
    engineInQueryInfo: {
      type: String,
      required: false
    },
    datasetInfo: {
      type: Object,
      required: true
    },
    engineInDatasetInfo: {
      type: String,
      required: false
    },
    diff: {
      type: Object,
      required: true
    }
  },
  methods: {
    forwardShowInfo(row, id) {
      this.$emit('showInfo', row, id);
    }
  }
};
</script>
