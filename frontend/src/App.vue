/* Copyright (C) 2023 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/
<template>
  <div id="app">
    <Toast v-bind:error="errorMessage"></Toast>
    <Preloader v-bind:visible="preloaderVisible"></Preloader>
    <div class="container">
      <div class="row">
        <div class="col-12 text-left">
          <img alt="logo" class="d-block mb-1" src="./assets/logo.svg"
               style="margin-left: auto; margin-right: auto; width:50%;">
          <h4 align="center" dir="auto">
            <a href="/about/">About</a> •
            <a href="/principles/">Testing principles</a> •
            <a href="/framework/">Test framework</a> •
            <a href="/posts/">About tests</a> •
            <a href="https://github.com/db-benchmarks/db-benchmarks">GitHub <img src="./assets/github.png" height="22"
                                                                                 style="vertical-align: middle; padding-bottom: 4px;"/></a>
            •
            <a href="https://twitter.com/db_benchmarks">Twitter<img src="./assets/twitter.png" height="22"
                                                                    style="vertical-align: middle; padding-bottom: 3px; padding-left: 4px;"/></a>
          </h4>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row">
        <div class="col-12 text-left">
          <h1 align="center" style="padding-top: 10px;"><a href="https://github.com/db-benchmarks/db-benchmarks">⭐Star
            us on GitHub⭐</a></h1>
        </div>
      </div>
      <div class="row">
        <div class="col-12 text-left">
          <div align="center">
            <small>Open source is hard. It motivates us a lot.</small>
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row mt-2">
        <div class="col-12 col-md-2">
          <h4>Benchmark</h4>
        </div>
        <div class="col-12 col-md-10">
          <ButtonGroup v-bind:items="benchmarks"
                       v-bind:switch="true"
                       v-bind:capitalize="true"
                       v-on:changed="handleBenchmarkChange"/>
        </div>
      </div>
      <div class="row mt-2" v-if="isFulltextView">
        <div class="col-12 col-md-1">
          <h4>Test</h4>
        </div>
        <div class="col-12 col-md-11">
          <ButtonGroup v-bind:items="tests"
                       v-bind:switch="true"
                       v-bind:capitalize="false"
                       v-on:changed="cleanUrl();fillMemory();"/>
        </div>
      </div>
      <div class="row mt-2" v-else>
        <div class="col-12 col-md-1">
          <h4>Dataset</h4>
        </div>
        <div class="col-12 col-md-11">
          <ButtonGroup v-bind:items="vectorDatasets"
                       v-bind:switch="true"
                       v-bind:capitalize="false"
                       v-on:changed="onVectorDatasetChanged"/>
        </div>
      </div>
      <div class="row mt-2" v-if="isFulltextView">
        <div class="col">
          <TestInfo v-bind:test-info="testsInfo"
                    v-bind:short-server-info="shortServerInfo"
                    v-bind:full-server-info="fullServerInfo">
          </TestInfo>
        </div>
      </div>
      <div class="row mt-4">
        <div class="col-12">
          <h4>Engines</h4>
          <EngineGroup v-if="isFulltextView"
                       v-bind:groups="engineGroups"
                       v-bind:items="engines"
                       v-on:changed="fillEngineGroups();applySelection(false)">
          </EngineGroup>
          <EngineGroup v-else
                       v-bind:groups="vectorEngineGroups"
                       v-bind:items="vectorEngines"
                       v-on:changed="onVectorEnginesChanged">
          </EngineGroup>
        </div>
      </div>
      <div class="row mt-4" v-if="isFulltextView">
        <div class="col-5">
          <h4>RAM limit</h4>
          <ButtonGroup v-bind:items="memory"
                       v-bind:switch="true"
                       v-bind:capitalize="false"
                       v-bind:append="'MB'"
                       v-on:changed="fillEngines()"/>
        </div>

        <div class="col-3">
          <h4>Result</h4>
          <ButtonGroup v-bind:items="cache"
                       v-bind:switch="false"
                       v-bind:capitalize="true "
                       v-on:changed="applySelection(false)"/>
        </div>
      </div>

      <FulltextBenchmark v-if="isFulltextView"
                         v-bind:engines="getSelectedRow(this.engines)"
                         v-bind:cache="prepareCacheForTable"
                         v-bind:filtered-results="filteredResults"
                         v-bind:checksums="checksums"
                         v-bind:retest-engines="retestEngines"
                         v-bind:queries="queries"
                         v-bind:ingesting-results-filtered="ingestingResultsFiltered"
                         v-bind:ingesting-results-visibility="ingestingResultsVisibility"
                         v-bind:parsed-query-info="parsedQueryInfo"
                         v-bind:query-info="queryInfo"
                         v-bind:engine-in-query-info="engineInQueryInfo"
                         v-bind:dataset-info="datasetInfo"
                         v-bind:engine-in-dataset-info="engineInDatasetInfo"
                         v-bind:diff="diff"
                         v-on:update:checked="modifyUrl()"
                         v-on:showDiff="showDiff"
                         v-on:showInfo="showInfo"
                         v-on:showDatasetInfo="showDatasetInfo"
                         v-on:showRetestResults="showRetestResults"
      />
      <VectorBenchmark v-else
                       v-bind:selected-dataset="selectedVectorDataset"
                       v-bind:selected-engines="selectedVectorEngines"
                       v-bind:initial-precision="vectorPrecision"
                       v-bind:initial-plot-metric="vectorPlotMetric"
                       v-bind:initial-parallel="vectorParallel"
                       v-bind:initial-storage-engine="vectorStorageEngine"
                       v-bind:initial-optimize-cutoff="vectorOptimizeCutoff"
                       v-bind:initial-auto-optimize="vectorAutoOptimize"
                       v-bind:initial-m="vectorM"
                       v-bind:initial-ef-build="vectorEfBuild"
                       v-bind:initial-ef-search="vectorEfSearch"
                       v-on:meta-ready="handleVectorMeta"
                       v-on:state-change="handleVectorStateChange"/>
      <footer class="my-5 pt-5 text-muted text-center text-small">
      </footer>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import EngineGroup from "@/components/EngineGroup";
import ButtonGroup from "@/components/ButtonGroup";
import TestInfo from "@/components/TestInfo";
import JQuery from 'jquery'
import Toast from "@/components/Toast.vue";
import Preloader from "./components/Preloader";
import FulltextBenchmark from "@/components/FulltextBenchmark.vue";
import VectorBenchmark from "@/components/VectorBenchmark.vue";

export default {
  name: 'App',
  components: {
    Preloader,
    TestInfo,
    ButtonGroup,
    EngineGroup,
    Toast,
    FulltextBenchmark,
    VectorBenchmark
  },
  data() {
    return {
      benchmarks: [{"fulltext": 1}, {"vector": 0}],
      vectorPrecision: 0.9,
      vectorPlotMetric: 'rps',
      vectorParallel: null,
      vectorStorageEngine: null,
      vectorOptimizeCutoff: null,
      vectorAutoOptimize: null,
      vectorM: null,
      vectorEfBuild: null,
      vectorEfSearch: null,
      engines: [],
      engineGroups: {},
      vectorDatasets: [],
      vectorEngines: [],
      vectorEngineGroups: {},
      vectorDatasetEngines: null,
      results: {},
      initData: {},
      errorMessage: "",
      filteredResults: [],
      tests: [],
      testsInfo: [],
      shortServerInfo: [],
      fullServerInfo: [],
      memory: [],
      queries: [],
      // A list of checksums of queries currently selected for comparison
      checksums: {},
      retestEngines: [],
      resultsCount: 0,
      selectedTest: 0,
      ingestingResults: [],
      ingestingResultsFiltered: [],
      ingestingResultsVisibility: false,
      queryInfo: {},
      parsedQueryInfo: {},
      // A list of ids of queries currently selected for comparison
      compareIds: [],
      diff: {},
      datasetInfo: {},
      cache: [{"fastest": 0}, {"slowest": 0}, {"fast_avg": 1}],
      engineInQueryInfo: null,
      engineInDatasetInfo: null,
      apiCallTimeoutMs: 20000,
      preloaderVisible: false,
      vectorUrlState: null,
    }
  },
  created: function () {
    this.vectorUrlState = this.getVectorStateFromUrl();
    this.applyBenchmarkFromUrl();
    this.preloaderVisible = true;
    axios
        .get(this.getServerUrl + this.getApiPath, {timeout: this.apiCallTimeoutMs})
        .then(response => {
          this.init(response.data.result);
          this.preloaderVisible = false;
        })
        .catch(error => {
          this.showToast(error.message);
          this.preloaderVisible = false;
        });
  },
  watch: {
    queryInfo: function () {
      this.parsedQueryInfo = {};
      for (let tab in this.queryInfo) {
        if (this.queryInfo[tab] instanceof Object) {
          let text = '<ul>';

          for (let row in this.queryInfo[tab]) {
            text += '<li><strong>' + row + ':</strong> ' + this.queryInfo[tab][row] + '</li>'
          }

          text += '</ul>';
          this.parsedQueryInfo[tab] = text;
        } else {
          let escape = document.createElement('textarea');
          escape.textContent = this.queryInfo[tab];
          this.parsedQueryInfo[tab] = escape.innerHTML;
        }
      }
    }
  },
  methods: {
    handleBenchmarkChange() {
      if (this.isFulltextView) {
        this.modifyUrl();
      } else {
        this.updateVectorUrl();
      }
    },
    onVectorDatasetChanged() {
      this.applyVectorEnginesForDataset();
      this.updateVectorEngineGroups();
      this.updateVectorUrl();
    },
    onVectorEnginesChanged() {
      this.updateVectorEngineGroups();
      this.updateVectorUrl();
    },
    handleVectorMeta(meta) {
      if (!meta) {
        return;
      }
      if (meta.datasets) {
        this.vectorDatasets = this.buildSelectionItems(meta.datasets, this.vectorDatasets);
      }
      if (meta.dataset_engines) {
        this.vectorDatasetEngines = meta.dataset_engines;
      }
      if (meta.engines) {
        this.vectorEngines = this.buildSelectionItems(meta.engines, this.vectorEngines);
      }
      this.applyVectorStateFromUrl();
      this.applyVectorEnginesForDataset();
      this.updateVectorEngineGroups();
    },
    handleVectorStateChange(state) {
      if (!state) {
        return;
      }
      if (state.precision !== undefined && state.precision !== null) {
        this.vectorPrecision = state.precision;
      }
      if (state.plotMetric) {
        this.vectorPlotMetric = state.plotMetric;
      }
      if (state.parallel !== undefined) {
        this.vectorParallel = state.parallel;
      }
      if (state.storageEngine !== undefined) {
        this.vectorStorageEngine = state.storageEngine;
      }
      if (state.optimizeCutoff !== undefined) {
        this.vectorOptimizeCutoff = state.optimizeCutoff;
      }
      if (state.autoOptimize !== undefined) {
        this.vectorAutoOptimize = state.autoOptimize;
      }
      if (state.m !== undefined) {
        this.vectorM = state.m;
      }
      if (state.efBuild !== undefined) {
        this.vectorEfBuild = state.efBuild;
      }
      if (state.efSearch !== undefined) {
        this.vectorEfSearch = state.efSearch;
      }
      this.updateVectorUrl();
    },
    applyVectorEnginesForDataset() {
      if (!this.vectorDatasetEngines || !this.selectedVectorDataset) {
        return;
      }
      const datasetEngines = this.vectorDatasetEngines[this.selectedVectorDataset];
      if (!Array.isArray(datasetEngines)) {
        return;
      }
      this.vectorEngines = this.buildSelectionItems(datasetEngines, this.vectorEngines);
    },
    buildSelectionItems(items, previousItems) {
      if (!Array.isArray(items)) {
        return [];
      }
      const previousSelected = new Set(this.getSelectedRow(previousItems));
      const rows = items.map((name) => {
        const row = {};
        row[name] = previousSelected.has(name) ? 1 : 0;
        return row;
      });
      if (rows.length > 0 && this.getSelectedRow(rows).length === 0) {
        rows[0][Object.keys(rows[0])[0]] = 1;
      }
      return rows;
    },
    updateVectorEngineGroups() {
      this.vectorEngineGroups = {};
      const engineNames = [];
      for (let row of this.vectorEngines) {
        engineNames.push(Object.keys(row)[0]);
      }
      for (let engineFullName of engineNames) {
        let engineName = engineFullName.split('_')[0];
        if (this.vectorEngineGroups[engineName] === undefined) {
          this.vectorEngineGroups[engineName] = {};
        }
        let selected = false;
        for (let row of this.vectorEngines) {
          if (row[engineFullName] !== undefined) {
            selected = (row[engineFullName] !== 0 && row[engineFullName] !== false);
          }
        }
        this.vectorEngineGroups[engineName][engineFullName] = selected;
      }
    },
    getVectorStateFromUrl() {
      const params = this.getUrlParams();
      const state = {};
      const benchmark = params.get('benchmark');
      if (benchmark) {
        state.benchmark = benchmark;
      }
      const dataset = params.get('v_dataset');
      if (dataset) {
        state.dataset = dataset;
      }
      const engines = params.get('v_engines');
      if (engines) {
        state.engines = engines.split(',').filter((item) => item.length > 0);
      }
      const precision = params.get('v_precision');
      if (precision) {
        const parsed = parseFloat(precision);
        if (!Number.isNaN(parsed)) {
          state.precision = parsed;
        }
      }
      const plot = params.get('v_plot');
      if (plot) {
        state.plotMetric = plot;
      }
      const parallel = params.get('v_parallel');
      if (parallel) {
        const parsed = parseInt(parallel, 10);
        if (!Number.isNaN(parsed)) {
          state.parallel = parsed;
        }
      }
      const storageEngine = params.get('v_storage');
      if (storageEngine) {
        state.storageEngine = storageEngine;
      }
      const optimizeCutoff = params.get('v_optimize_cutoff');
      if (optimizeCutoff) {
        const parsed = parseInt(optimizeCutoff, 10);
        if (!Number.isNaN(parsed)) {
          state.optimizeCutoff = parsed;
        }
      }
      const autoOptimize = params.get('v_auto_optimize');
      if (autoOptimize) {
        const parsed = parseInt(autoOptimize, 10);
        if (!Number.isNaN(parsed)) {
          state.autoOptimize = parsed;
        }
      }
      const m = params.get('v_m');
      if (m) {
        const parsed = parseInt(m, 10);
        if (!Number.isNaN(parsed)) {
          state.m = parsed;
        }
      }
      const efBuild = params.get('v_ef_build');
      if (efBuild) {
        const parsed = parseInt(efBuild, 10);
        if (!Number.isNaN(parsed)) {
          state.efBuild = parsed;
        }
      }
      const efSearch = params.get('v_ef_search');
      if (efSearch) {
        const parsed = parseInt(efSearch, 10);
        if (!Number.isNaN(parsed)) {
          state.efSearch = parsed;
        }
      }
      return state;
    },
    applyBenchmarkFromUrl() {
      if (!this.vectorUrlState || !this.vectorUrlState.benchmark) {
        return;
      }
      if (this.vectorUrlState.benchmark === 'vector') {
        this.setSelection('vector', this.benchmarks);
      } else if (this.vectorUrlState.benchmark === 'fulltext') {
        this.setSelection('fulltext', this.benchmarks);
      }
    },
    applyVectorStateFromUrl() {
      if (!this.vectorUrlState) {
        return;
      }
      if (this.vectorUrlState.dataset) {
        this.setSelection(this.vectorUrlState.dataset, this.vectorDatasets);
      }
      if (this.vectorUrlState.engines && this.vectorUrlState.engines.length) {
        this.setSelection(this.vectorUrlState.engines.join(','), this.vectorEngines);
        this.updateVectorEngineGroups();
      }
      if (this.vectorUrlState.precision !== undefined) {
        this.vectorPrecision = this.vectorUrlState.precision;
      }
      if (this.vectorUrlState.plotMetric) {
        this.vectorPlotMetric = this.vectorUrlState.plotMetric;
      }
      if (this.vectorUrlState.parallel !== undefined) {
        this.vectorParallel = this.vectorUrlState.parallel;
      }
      if (this.vectorUrlState.storageEngine !== undefined) {
        this.vectorStorageEngine = this.vectorUrlState.storageEngine;
      }
      if (this.vectorUrlState.optimizeCutoff !== undefined) {
        this.vectorOptimizeCutoff = this.vectorUrlState.optimizeCutoff;
      }
      if (this.vectorUrlState.autoOptimize !== undefined) {
        this.vectorAutoOptimize = this.vectorUrlState.autoOptimize;
      }
      if (this.vectorUrlState.m !== undefined) {
        this.vectorM = this.vectorUrlState.m;
      }
      if (this.vectorUrlState.efBuild !== undefined) {
        this.vectorEfBuild = this.vectorUrlState.efBuild;
      }
      if (this.vectorUrlState.efSearch !== undefined) {
        this.vectorEfSearch = this.vectorUrlState.efSearch;
      }
    },
    updateVectorUrl() {
      if (!this.isFulltextView) {
        const params = new URLSearchParams();
        params.set('benchmark', 'vector');
        if (this.selectedVectorDataset) {
          params.set('v_dataset', this.selectedVectorDataset);
        }
        const engines = this.selectedVectorEngines;
        if (engines.length) {
          params.set('v_engines', engines.join(','));
        }
        params.set('v_precision', this.vectorPrecision.toFixed(2));
        if (this.vectorPlotMetric) {
          params.set('v_plot', this.vectorPlotMetric);
        }
        if (this.vectorParallel !== undefined && this.vectorParallel !== null) {
          params.set('v_parallel', String(this.vectorParallel));
        }
        if (this.vectorStorageEngine) {
          params.set('v_storage', this.vectorStorageEngine);
        }
        if (this.vectorOptimizeCutoff !== undefined && this.vectorOptimizeCutoff !== null) {
          params.set('v_optimize_cutoff', String(this.vectorOptimizeCutoff));
        }
        if (this.vectorAutoOptimize !== undefined && this.vectorAutoOptimize !== null) {
          params.set('v_auto_optimize', String(this.vectorAutoOptimize));
        }
        if (this.vectorM !== undefined && this.vectorM !== null) {
          params.set('v_m', String(this.vectorM));
        }
        if (this.vectorEfBuild !== undefined && this.vectorEfBuild !== null) {
          params.set('v_ef_build', String(this.vectorEfBuild));
        }
        if (this.vectorEfSearch !== undefined && this.vectorEfSearch !== null) {
          params.set('v_ef_search', String(this.vectorEfSearch));
        }
        window.history.pushState("", "", "/?" + params.toString());
      }
    },
    init(data) {
      this.initData = data;

      // Test list init
      for (let testName of Object.keys(data)) {
        let row = {};
        row[testName] = 0
        this.tests.push(row)
      }

      this.shuffleSelectionIfNonSelected('tests');

      this.fillMemory();

    },

    getIngestingResultsData() {
      let testName = this.getSelectedRow(this.tests)[0];
      this.preloaderVisible = true;
      this.ingestingResultsVisibility = false;
      axios
          .get(this.getServerUrl + this.getApiPath + "?ingesting_info=1&test_name=" + testName,
              {timeout: this.apiCallTimeoutMs})
          .then(response => {
            this.ingestingResults = response.data.result
            if (this.ingestingResults.length > 0) {
              this.filterEnginesInIngestingTable()
            }
            this.preloaderVisible = false;
          })
          .catch(error => {
            this.showToast(error.message);
            this.preloaderVisible = false;
          });
    },

    getTestData(clearQueries = false) {
      this.preloaderVisible = true;

      let testName = this.getSelectedRow(this.tests)[0];

      axios
          .get(this.getServerUrl + this.getApiPath +
              '?server_info=1&test_name=' + testName,
              {timeout: this.apiCallTimeoutMs})
          .then(response => {
            this.testsInfo = response.data.result.testsInfo;
            this.shortServerInfo = response.data.result.shortServerInfo;
            this.fullServerInfo = this.parseFullServerInfo(response.data.result.fullServerInfo);
            this.preloaderVisible = false;
          })
          .catch(error => {
            this.showToast(error.message);
            this.preloaderVisible = false;
          });

      axios
          .get(this.getServerUrl + this.getApiPath +
              '?test_name=' + testName +
              '&memory=' + this.getSelectedRow(this.memory)[0],
              {timeout: this.apiCallTimeoutMs})
          .then(response => {
            this.results = response.data.result.data;
            this.applySelection(clearQueries);
            this.preloaderVisible = false;
          })
          .catch(error => {
            this.showToast(error.message);
            this.preloaderVisible = false;
          });
    },

    filterEnginesInIngestingTable() {
      let selectedEngines = this.getSelectedRow(this.engines)

      this.ingestingResultsFiltered = this.ingestingResults.filter((row) => {

        for (let selectedEngine of selectedEngines) {
          let fullName = this.assembleName(row.engine_name, row.version, row.type)
          if (fullName === selectedEngine) {
            return true
          }
        }
        return false;
      });

      if (this.ingestingResultsFiltered.length > 0) {
        this.ingestingResultsVisibility = true;
      }
    },

    assembleName(engineName, version, type) {
      if (type.length > 0) {
        type = "_" + type
      }
      if (version.length > 0) {
        version = "_" + version
      }
      return engineName + type + version
    },

    shuffleSelectionIfNonSelected(type) {
      if (['cache', 'engines', 'tests', 'memory'].includes(type)) {
        let selection = this.getSelectionFromUrl(type);
        if (!selection) {
          let randomSelection = Math.floor(Math.random() * this[type].length)
          for (let index in this[type][randomSelection]) {
            if (this[type][randomSelection][index] === 1 && this[type].length > 1) {
              this.shuffleSelectionIfNonSelected(type);
            } else {
              this[type][randomSelection][index] = 1;
            }
          }
        }
      } else {
        throw new Error('Can\'t shuffle non existing selection');
      }
    },
    fillMemory() {
      let selectedTest = this.getSelectedRow(this.tests)[0];

      let memory = [];

      for (let memValue in this.initData[selectedTest]) {
        let obj = {};
        obj[memValue] = 0;
        memory.push(obj)
      }


      this.memory = memory.sort(function (a, b) {
        if (Object.keys(a)[0] < Object.keys(b)[0]) {
          return 1;
        }
        if (Object.keys(a)[0] > Object.keys(b)[0]) {
          return -1;
        }

        return 0;
      });

      this.shuffleSelectionIfNonSelected('memory');
      this.fillEngines();
    },

    fillEngines() {
      let selectedTest = this.getSelectedRow(this.tests)[0];
      let selectedMemory = this.getSelectedRow(this.memory)[0];
      let engines = [];
      let retestEngines = [];

      let unfilteredEngines = this.initData[selectedTest][selectedMemory];

      for (let engineName of unfilteredEngines.filter(name => name.indexOf('_retest') >= 0)) {
        let obj = {};
        obj[engineName] = 0;
        retestEngines.push(obj)
      }

      this.retestEngines = retestEngines;

      this.availableEngines = unfilteredEngines.filter(name => name.indexOf('_retest') === -1);
      for (let engineName of this.availableEngines) {
        let obj = {};
        obj[engineName] = 0;
        engines.push(obj)
      }

      this.engines = engines;
      this.shuffleSelectionIfNonSelected('engines');
      this.shuffleSelectionIfNonSelected('engines');
      this.fillEngineGroups();
      this.getCacheSelection();
      this.getTestData();
      this.getIngestingResultsData();
    },

    getCacheSelection() {
      this.getSelectionFromUrl("cache");
    },

    fillEngineGroups() {
      this.removeRetestEngine();
      this.engineGroups = {};
      for (let engineFullName of this.availableEngines) {

        let engineName = engineFullName.split('_');
        let selected = 0;

        if (this.engineGroups[engineName[0]] === undefined) {
          this.engineGroups[engineName[0]] = {};
        }

        for (let row of this.engines) {
          if (row[engineFullName] !== undefined) {
            selected = (row[engineFullName] !== 0 && row[engineFullName] !== false);
          }
        }
        this.engineGroups[engineName[0]][engineFullName] = selected;
      }
      this.filterEnginesInIngestingTable();
    },

    showToast(error) {
      this.errorMessage = error;
      JQuery('.toast').toast({autohide: false}).toast('show')
    },

    showDatasetInfo(engine) {
      this.datasetInfo['info'] = {};
      this.preloaderVisible = true;
      axios
          .get(this.getServerUrl + this.getApiPath + '?dataset_info=1&id=' + this.compareIds[0][engine], {timeout: this.apiCallTimeoutMs})
          .then(response => {
            this.datasetInfo = response.data.result;
            this.engineInDatasetInfo = engine;
            JQuery('#modal-dataset-info').modal('show');
            this.preloaderVisible = false;
          })
          .catch(error => {
            this.showToast(error.message);
            this.preloaderVisible = false;
          })
    },

    removeRetestEngine(skipRetestEngines = false) {
      for (let key in this.engines) {
        for (let engineName in this.engines[key]) {
          if (engineName.indexOf('_retest') > 0) {
            this.$delete(this.engines, key)
          }
        }
      }

      if (!skipRetestEngines) {
        for (let engineKey in this.retestEngines) {
          for (let engineName in this.retestEngines[engineKey]) {
            this.retestEngines[engineKey][engineName] = 0;
          }
        }
      }
    },
    showRetestResults(engine) {

      this.removeRetestEngine(true);

      for (let engineKey in this.retestEngines) {
        if (Object.keys(this.retestEngines[engineKey]).includes(engine + "_retest")) {

          let isEnabled = this.retestEngines[engineKey][engine + "_retest"];

          if (!isEnabled) {
            let row = {};
            row[engine + "_retest"] = 1;
            this.engines.push(row);
          }
          this.retestEngines[engineKey][engine + "_retest"] = !isEnabled;
        }
      }


      this.engines = this.engines.sort(function (a, b) {
        if (Object.keys(a)[0] < Object.keys(b)[0]) {
          return -1;
        }
        if (Object.keys(a)[0] > Object.keys(b)[0]) {
          return 1;
        }

        return 0;
      });

      this.applySelection(false)
    },

    showInfo(row, id) {

      let selectedEngines = this.getSelectedRow(this.engines)
      let grouppedCount = this.prepareCacheForTable.length / selectedEngines.length;
      let index = Math.ceil(id / grouppedCount) - 1;

      let engineName = selectedEngines[index];
      this.engineInQueryInfo = engineName;
      let compareId = this.compareIds[row][engineName];
      this.preloaderVisible = true;
      axios.get(this.getServerUrl + this.getApiPath + '?info=1&id=' + compareId, {timeout: this.apiCallTimeoutMs})
          .then(response => {
            this.queryInfo = response.data.result;
            JQuery('#modal-query-info').modal('show');
            this.preloaderVisible = false;
          })
          .catch(error => {
            this.showToast(error.message);
            this.preloaderVisible = false;
          })
    },

    showDiff(row) {
      let ids = Object.values(this.compareIds[row]);
      this.preloaderVisible = true;
      axios.get(this.getServerUrl + this.getApiPath + "?compare=1&id1=" + ids[0] + "&id2=" + ids[1], {timeout: this.apiCallTimeoutMs})
          .then(response => {
            this.diff = response.data.result;
            JQuery('#modal-query-diff').modal('show');
            this.preloaderVisible = false;
          })
          .catch(error => {
            this.showToast(error.message);
            this.preloaderVisible = false;
          })
    },

    parseFullServerInfo(fullServerInfo) {
      return JSON.parse(fullServerInfo);
    },

    fillCheckedQueries(data) {

      let result = [];

      for (let selectedQuery in data) {
        result[selectedQuery] = true;
      }

      this.queries = result;
    },

    applySelection(clearQueries = true) {
      this.checksums = {};
      this.compareIds = [];
      let testResults = this.results[this.getSelectedRow(this.tests)[0]][this.getSelectedRow(this.memory)[0]];

      let results = [];
      let selectedEngines = this.getSelectedRow(this.engines);


      let selectedCache = this.getSelectedRow(this.cache);
      let i = 0;

      // Iterate through the hashes of test queries (each query stores results from different engines)
      for (let queryHash in testResults) {

        let queryString = [testResults[queryHash]['query']];
        for (let engine in selectedEngines) {


          let checkSum = this.checksums[i];
          if (checkSum === undefined) {
            checkSum = {};
          }

          let checksumValue = -1;
          let compareId = null;

          let engineName = selectedEngines[engine];

          if (testResults[queryHash][engineName] !== undefined) {
            checksumValue = testResults[queryHash][engineName]['checksum'];
            compareId = testResults[queryHash][engineName]['id'];
          } else {
            continue;
          }

          checkSum[engineName] = checksumValue;
          this.checksums[i] = checkSum;

          if (this.compareIds[i] === undefined) {
            this.compareIds[i] = {};
          }
          this.compareIds[i][engineName] = compareId;

          for (let cache in selectedCache) {
            let value;
            value = testResults[queryHash][engineName][selectedCache[cache]];
            if (value === -1) {
              value = testResults[queryHash][engineName]['error'];
            }
            queryString = queryString.concat(value);
          }
        }

        results.push(queryString)
        i++;
      }

      if (selectedEngines.length === 0) {
        results = [];
      }

      this.fillCheckedQueries(results);

      if (clearQueries) {
        this.modifyUrl();
      }

      this.getQuerySelectionsFromUrl();


      this.modifyUrl();
      this.resultsCount = results.length;
      this.filteredResults = results;
    },

    modifyUrl() {
      const params = new URLSearchParams({
        cache: this.getSelectedRow(this.cache),
        engines: this.getSelectedRow(this.engines),
        tests: this.getSelectedRow(this.tests),
        memory: this.getSelectedRow(this.memory),
        queries: this.getSelectedQueries(this.queries),
      });
      window.history.pushState("", "", "/?" + params.toString());
    },

    cleanUrl() {
      window.history.pushState("", "", "/");
    },

    getUrlParams() {
      return new URLSearchParams(window.location.search);
    },


    getQuerySelectionsFromUrl() {
      let urlQueries = this.getUrlParams().get('queries');


      if (urlQueries !== null) {
        if (urlQueries !== '') {
          let urlValues = urlQueries.split(",");
          // Clean section
          for (let row in this.queries) {
            this.queries[row] = false;
          }

          for (let selectionid in urlValues) {
            if (this.queries[urlValues[selectionid]] != null) {
              this.queries[urlValues[selectionid]] = true;
            }
          }
        }
      }
    },

    getSelectionFromUrl(type) {
      const urlParams = this.getUrlParams();

      let selected = false;
      if (['cache', 'engines', 'tests', 'memory'].includes(type)) {
        let selection = urlParams.get(type);
        if (selection != null && selection !== "") {
          this.setSelection(selection, this[type], type)
          selected = true;
        }
      } else {
        throw new Error('Can\'t get selection of non allowed parameter');
      }

      return selected;
    },

    setSelection(urlData, section) {
      // Clean section
      section.forEach((row) => {
        for (let id in row) {
          row[id] = 0;
        }
      });

      // set selection
      let urlValues = urlData.split(",");
      for (let selectionid in urlValues) {
        let currentSelection = urlValues[selectionid];

        section.forEach((row) => {
          if (row[currentSelection] != null) {
            row[currentSelection] = 1;
          }
        });
      }
    },

    getSelectedRow(obj) {
      let result = [];
      for (let engine in obj) {
        for (let item in obj[engine]) {
          if (obj[engine][item]) {
            result.push(item);
          }
        }
      }
      return result;
    },

    getSelectedQueries(obj) {
      let result = [];
      for (let row in obj) {
        if (obj[row] === true) {
          result.push(row);
        }
      }
      return result;
    },
  },
  computed: {
    isFulltextView() {
      const selected = this.getSelectedRow(this.benchmarks);
      return selected.length === 0 || selected[0] === 'fulltext';
    },
    selectedVectorDataset() {
      const selected = this.getSelectedRow(this.vectorDatasets);
      return selected.length > 0 ? selected[0] : null;
    },
    selectedVectorEngines() {
      return this.getSelectedRow(this.vectorEngines);
    },
    getYear() {
      let today = new Date();
      return today.getFullYear();
    },
    getServerUrl() {
      let serverUrl = process.env.VUE_APP_API_URL;
      if (serverUrl === undefined) {
        serverUrl = '';
      }
      return serverUrl;
    },
    getApiPath() {
      let apiPath = process.env.VUE_APP_API_PATH;
      if (apiPath === undefined) {
        apiPath = '/api';
      }
      return apiPath;
    },
    prepareCacheForTable() {
      let result = [];
      this.getSelectedRow(this.engines).forEach(() => {
        result = this.getSelectedRow(this.cache).concat(result)
      })
      return result
    },
  }
};
String.prototype.capitalize = function () {
  return this.charAt(0).toUpperCase() + this.slice(1);
}
</script>

<style>
@import '~bootstrap/dist/css/bootstrap.css';

h4, .h4 {
  font-weight: bold;
}

.info-tooltip-icon {
  width: 20px;
  height: 20px;
  cursor: pointer;
  box-shadow: 2px 2px 0 black;
  border-radius: 10px;
  margin-left: 6px;
  vertical-align: middle;
}

.caption {
  font-weight: 600;
  margin: 10px 20px;
}

.ingestion-disclaimer {
  color: #4a4a4a;
  font-size: 0.95rem;
  margin: 0 0 10px;
}


.tooltip {
  display: block !important;
  z-index: 10000;
}


.tooltip {
  display: block !important;
  z-index: 10000;
}

.tooltip .tooltip-inner {
  background: black;
  color: white;
  border-radius: 16px;
  padding: 5px 10px 4px;
}

.tooltip .tooltip-arrow {
  width: 0;
  height: 0;
  border-style: solid;
  position: absolute;
  margin: 5px;
  border-color: black;
}

.tooltip[x-placement^="top"] {
  margin-bottom: 5px;
}

.tooltip[x-placement^="top"] .tooltip-arrow {
  border-width: 5px 5px 0 5px;
  border-left-color: transparent !important;
  border-right-color: transparent !important;
  border-bottom-color: transparent !important;
  bottom: -5px;
  left: calc(50% - 5px);
  margin-top: 0;
  margin-bottom: 0;
}

.tooltip[x-placement^="bottom"] {
  margin-top: 5px;
}

.tooltip[x-placement^="bottom"] .tooltip-arrow {
  border-width: 0 5px 5px 5px;
  border-left-color: transparent !important;
  border-right-color: transparent !important;
  border-top-color: transparent !important;
  top: -5px;
  left: calc(50% - 5px);
  margin-top: 0;
  margin-bottom: 0;
}

.tooltip[x-placement^="right"] {
  margin-left: 5px;
}

.tooltip[x-placement^="right"] .tooltip-arrow {
  border-width: 5px 5px 5px 0;
  border-left-color: transparent !important;
  border-top-color: transparent !important;
  border-bottom-color: transparent !important;
  left: -5px;
  top: calc(50% - 5px);
  margin-left: 0;
  margin-right: 0;
}

.tooltip[x-placement^="left"] {
  margin-right: 5px;
}

.tooltip[x-placement^="left"] .tooltip-arrow {
  border-width: 5px 0 5px 5px;
  border-top-color: transparent !important;
  border-right-color: transparent !important;
  border-bottom-color: transparent !important;
  right: -5px;
  top: calc(50% - 5px);
  margin-left: 0;
  margin-right: 0;
}

.tooltip[aria-hidden='true'] {
  visibility: hidden;
  opacity: 0;
  transition: opacity .15s, visibility .15s;
}

.tooltip[aria-hidden='false'] {
  visibility: visible;
  opacity: 1;
  transition: opacity .15s;
}

</style>
