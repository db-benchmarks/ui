/* Copyright (C) 2023 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */
<template>
  <div>
    <div v-if="loading" class="row mt-4">
      <div class="col-12">
        <p>Loading vector benchmark data...</p>
      </div>
    </div>
    <div v-else-if="error" class="row mt-4">
      <div class="col-12">
        <div class="alert alert-warning">
          <strong>Vector data not available.</strong>
          <div class="mt-2">{{ error }}</div>
          <div class="mt-2">Run: <code>cd frontend && npm run build:vector</code></div>
        </div>
      </div>
    </div>
    <div v-else>
      <div class="row mt-4">
        <div class="col-12">
          <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4 class="mb-2">Vector search performance</h4>
          </div>
          <div class="plot-controls d-flex flex-wrap align-items-center">
            <div class="d-flex flex-wrap align-items-center mr-4">
              <label class="font-weight-bold mr-2 mb-0">Plot values:</label>
              <div class="form-check form-check-inline" v-for="option in plotOptions" :key="option.value">
                <input class="form-check-input" type="radio" :id="`plot-${option.value}`"
                       :value="option.value" v-model="plotMetric">
                <label class="form-check-label" :for="`plot-${option.value}`">{{ option.label }}</label>
              </div>
            </div>
            <div v-if="availableParallels.length" class="d-flex flex-wrap align-items-center">
              <label class="font-weight-bold mr-2 mb-0">{{ threadsLabel }}:</label>
              <select class="form-control form-control-sm w-auto" v-model.number="selectedParallel">
                <option v-for="parallel in availableParallels" :key="parallel" :value="parallel">
                  {{ parallel }}
                </option>
              </select>
            </div>
            <div v-if="availableStorageEngines.length" class="d-flex flex-wrap align-items-center ml-3">
              <label class="font-weight-bold mr-2 mb-0">Storage:</label>
              <select class="form-control form-control-sm w-auto" v-model="selectedStorageEngine">
                <option :value="null">Any</option>
                <option v-for="engine in availableStorageEngines" :key="engine" :value="engine">
                  {{ engine }}
                </option>
              </select>
            </div>
            <div v-if="availableOptimizeCutoffs.length" class="d-flex flex-wrap align-items-center ml-3">
              <label class="font-weight-bold mr-2 mb-0">Optimize cutoff:</label>
              <select class="form-control form-control-sm w-auto" v-model.number="selectedOptimizeCutoff">
                <option :value="null">Any</option>
                <option v-for="cutoff in availableOptimizeCutoffs" :key="cutoff" :value="cutoff">
                  {{ cutoff }}
                </option>
              </select>
            </div>
            <div v-if="availableAutoOptimize.length" class="d-flex flex-wrap align-items-center ml-3">
              <label class="font-weight-bold mr-2 mb-0">Auto optimize:</label>
              <select class="form-control form-control-sm w-auto" v-model.number="selectedAutoOptimize">
                <option :value="null">Any</option>
                <option v-for="value in availableAutoOptimize" :key="value" :value="value">
                  {{ value }}
                </option>
              </select>
            </div>
            <div v-if="availableMValues.length" class="d-flex flex-wrap align-items-center ml-3">
              <label class="font-weight-bold mr-2 mb-0">M:</label>
              <select class="form-control form-control-sm w-auto" v-model.number="selectedM">
                <option :value="null">Any</option>
                <option v-for="value in availableMValues" :key="value" :value="value">
                  {{ value }}
                </option>
              </select>
            </div>
            <div v-if="availableEfBuildValues.length" class="d-flex flex-wrap align-items-center ml-3">
              <label class="font-weight-bold mr-2 mb-0">EF (build):</label>
              <select class="form-control form-control-sm w-auto" v-model.number="selectedEfBuild">
                <option :value="null">Any</option>
                <option v-for="value in availableEfBuildValues" :key="value" :value="value">
                  {{ value }}
                </option>
              </select>
            </div>
            <div v-if="availableEfSearchValues.length" class="d-flex flex-wrap align-items-center ml-3">
              <label class="font-weight-bold mr-2 mb-0">EF (search):</label>
              <select class="form-control form-control-sm w-auto" v-model.number="selectedEfSearch">
                <option :value="null">Any</option>
                <option v-for="value in availableEfSearchValues" :key="value" :value="value">
                  {{ value }}
                </option>
              </select>
            </div>
          </div>
          <div ref="scatterChart" class="vector-chart"></div>
          <div class="precision-control precision-control--below">
            <label class="font-weight-bold">Precision threshold: {{ formatPrecision(precisionThreshold) }}</label>
            <input type="range"
                   :min="precisionBounds.min"
                   :max="precisionBounds.max"
                   :step="precisionStep"
                   v-model.number="precisionThreshold" />
          </div>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col-12">
          <h5>{{ isIndexTime ? 'Upload results' : 'Search results' }}</h5>
          <div v-if="isIndexTime ? sortedUploadTableRecords.length === 0 : filteredTableRecords.length === 0"
               class="alert alert-warning">
            {{ isIndexTime ? 'No upload results available for the selected engines.' : 'No engines meet precision threshold — lower the slider.' }}
          </div>
          <table v-else-if="!isIndexTime" class="table table-sm">
            <thead>
            <tr>
              <th>Engine</th>
              <th>Experiment</th>
              <th>Parallel</th>
              <th>Search idx</th>
              <th>Num candidates</th>
              <th>Options</th>
              <th>Index options</th>
              <th>Runs</th>
              <th role="button" @click="setSort('rps')">RPS</th>
              <th role="button" @click="setSort('p95_ms')">p95 (ms)</th>
              <th>p99 (ms)</th>
              <th>Index time (ms)</th>
              <th role="button" @click="setSort('mean_precision')">Precision</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="record in sortedTableRecords"
                :key="record.engine + '-' + record.experiment + '-' + record.parallel + '-' + record.searchIndex + '-' + record.num_candidates + '-' + record.last_timestamp">
              <td>{{ record.engine }}</td>
              <td>{{ record.experiment }}</td>
              <td>{{ record.parallel }}</td>
              <td>{{ formatOptional(record.searchIndex) }}</td>
              <td>{{ formatOptional(record.num_candidates) }}</td>
              <td v-html="formatObjectList(record.options)"></td>
              <td v-html="formatObjectList(record.collection_params)"></td>
              <td>{{ record.runs }}</td>
              <td>{{ formatRps(record.rps) }}</td>
              <td>{{ formatMs(record.p95_ms) }}</td>
              <td>{{ formatMs(record.p99_ms) }}</td>
              <td>{{ formatMs(getUploadTime(record)) }}</td>
              <td>{{ formatPrecision(record.mean_precision) }}</td>
            </tr>
            </tbody>
          </table>
          <table v-else class="table table-sm">
            <thead>
            <tr>
              <th>Engine</th>
              <th>Experiment</th>
              <th>Parallel</th>
              <th>Options</th>
              <th>Index options</th>
              <th>Runs</th>
              <th role="button" @click="setUploadSort('upload_ms')">Upload time (ms)</th>
              <th>Total upload (ms)</th>
              <th>Timestamp</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="record in sortedUploadTableRecords"
                :key="record.engine + '-' + record.experiment + '-' + record.parallel + '-' + record.last_timestamp">
              <td>{{ record.engine }}</td>
              <td>{{ record.experiment }}</td>
              <td>{{ record.parallel }}</td>
              <td v-html="formatObjectList(record.options)"></td>
              <td v-html="formatObjectList(record.collection_params)"></td>
              <td>{{ record.runs }}</td>
              <td>{{ formatMs(record.upload_ms) }}</td>
              <td>{{ formatMs(record.total_upload_ms) }}</td>
              <td>{{ record.last_timestamp || '-' }}</td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import * as echarts from "echarts";

export default {
  name: 'VectorBenchmark',
  props: {
    selectedDataset: {
      type: String,
      required: false
    },
    selectedEngines: {
      type: Array,
      required: true
    },
    initialPrecision: {
      type: Number,
      required: false
    },
    initialPlotMetric: {
      type: String,
      required: false
    },
    initialParallel: {
      type: Number,
      required: false
    },
    initialStorageEngine: {
      type: String,
      required: false
    },
    initialOptimizeCutoff: {
      type: Number,
      required: false
    },
    initialAutoOptimize: {
      type: Number,
      required: false
    },
    initialM: {
      type: Number,
      required: false
    },
    initialEfBuild: {
      type: Number,
      required: false
    },
    initialEfSearch: {
      type: Number,
      required: false
    }
  },
  data() {
    return {
      loading: true,
      error: '',
      vectorData: null,
      precisionThreshold: this.initialPrecision !== undefined ? this.initialPrecision : 0.9,
      plotMetric: this.initialPlotMetric || 'rps',
      selectedParallel: this.initialParallel !== undefined ? this.initialParallel : null,
      selectedStorageEngine: this.initialStorageEngine || null,
      selectedOptimizeCutoff: this.initialOptimizeCutoff !== undefined ? this.initialOptimizeCutoff : null,
      selectedAutoOptimize: this.initialAutoOptimize !== undefined ? this.initialAutoOptimize : null,
      selectedM: this.initialM !== undefined ? this.initialM : null,
      selectedEfBuild: this.initialEfBuild !== undefined ? this.initialEfBuild : null,
      selectedEfSearch: this.initialEfSearch !== undefined ? this.initialEfSearch : null,
      precisionBounds: { min: 0, max: 1 },
      precisionStep: 0.001,
      scatterChart: null,
      sortKey: 'rps',
      sortDirection: 'desc',
      uploadSortKey: 'upload_ms',
      uploadSortDirection: 'asc'
    };
  },
  computed: {
    isIndexTime() {
      return this.plotMetric === 'index_time';
    },
    threadsLabel() {
      return this.isIndexTime ? 'Upload threads' : 'Search threads';
    },
    plotOptions() {
      return [
        { value: 'rps', label: 'RPS' },
        { value: 'latency', label: 'Latency' },
        { value: 'p95', label: 'p95 latency' },
        { value: 'p99', label: 'p99 latency' },
        { value: 'index_time', label: 'Index time' }
      ];
    },
    dataset() {
      if (this.selectedDataset) {
        return this.selectedDataset;
      }
      if (this.vectorData && this.vectorData.datasets && this.vectorData.datasets.length > 0) {
        return this.vectorData.datasets[0];
      }
      return null;
    },
    searchRecords() {
      if (!this.vectorData || !this.vectorData.search_records) {
        return [];
      }
      return this.vectorData.search_records;
    },
    availableParallels() {
      if (!this.dataset) {
        return [];
      }
      const selectedEngines = new Set(this.selectedEngines || []);
      const parallels = new Set();
      const sourceRecords = this.isIndexTime ? this.uploadRecords : this.searchRecords;
      for (const record of sourceRecords) {
        if (record.dataset !== this.dataset) {
          continue;
        }
        if (!selectedEngines.has(record.engine)) {
          continue;
        }
        if (record.parallel == null) {
          continue;
        }
        parallels.add(record.parallel);
      }
      return Array.from(parallels).sort((a, b) => b - a);
    },
    availableStorageEngines() {
      if (!this.dataset) {
        return [];
      }
      const selectedEngines = new Set(this.selectedEngines || []);
      const values = new Set();
      const sourceRecords = this.isIndexTime ? this.uploadRecords : this.searchRecords;
      for (const record of sourceRecords) {
        if (record.dataset !== this.dataset) {
          continue;
        }
        if (!selectedEngines.has(record.engine)) {
          continue;
        }
        const storageEngine = record.collection_params ? record.collection_params.engine : null;
        if (storageEngine == null) {
          continue;
        }
        values.add(storageEngine);
      }
      return Array.from(values).sort();
    },
    availableOptimizeCutoffs() {
      if (!this.dataset) {
        return [];
      }
      const selectedEngines = new Set(this.selectedEngines || []);
      const values = new Set();
      const sourceRecords = this.isIndexTime ? this.uploadRecords : this.searchRecords;
      for (const record of sourceRecords) {
        if (record.dataset !== this.dataset) {
          continue;
        }
        if (!selectedEngines.has(record.engine)) {
          continue;
        }
        const cutoff = record.collection_params ? record.collection_params.optimize_cutoff : null;
        if (cutoff == null) {
          continue;
        }
        values.add(cutoff);
      }
      return Array.from(values).sort((a, b) => b - a);
    },
    availableAutoOptimize() {
      if (!this.dataset) {
        return [];
      }
      const selectedEngines = new Set(this.selectedEngines || []);
      const values = new Set();
      const sourceRecords = this.isIndexTime ? this.uploadRecords : this.searchRecords;
      for (const record of sourceRecords) {
        if (record.dataset !== this.dataset) {
          continue;
        }
        if (!selectedEngines.has(record.engine)) {
          continue;
        }
        const value = record.collection_params ? record.collection_params.auto_optimize : null;
        if (value == null) {
          continue;
        }
        values.add(value);
      }
      return Array.from(values).sort((a, b) => b - a);
    },
    availableMValues() {
      if (!this.dataset) {
        return [];
      }
      const selectedEngines = new Set(this.selectedEngines || []);
      const values = new Set();
      const sourceRecords = this.isIndexTime ? this.uploadRecords : this.searchRecords;
      for (const record of sourceRecords) {
        if (record.dataset !== this.dataset) {
          continue;
        }
        if (!selectedEngines.has(record.engine)) {
          continue;
        }
        const value = record.normalized_params ? record.normalized_params.m : null;
        if (value == null) {
          continue;
        }
        values.add(value);
      }
      return Array.from(values).sort((a, b) => b - a);
    },
    availableEfBuildValues() {
      if (!this.dataset) {
        return [];
      }
      const selectedEngines = new Set(this.selectedEngines || []);
      const values = new Set();
      const sourceRecords = this.isIndexTime ? this.uploadRecords : this.searchRecords;
      for (const record of sourceRecords) {
        if (record.dataset !== this.dataset) {
          continue;
        }
        if (!selectedEngines.has(record.engine)) {
          continue;
        }
        const value = record.normalized_params ? record.normalized_params.ef_build : null;
        if (value == null) {
          continue;
        }
        values.add(value);
      }
      return Array.from(values).sort((a, b) => b - a);
    },
    availableEfSearchValues() {
      if (!this.dataset) {
        return [];
      }
      const selectedEngines = new Set(this.selectedEngines || []);
      const values = new Set();
      const sourceRecords = this.isIndexTime ? this.uploadRecords : this.searchRecords;
      for (const record of sourceRecords) {
        if (record.dataset !== this.dataset) {
          continue;
        }
        if (!selectedEngines.has(record.engine)) {
          continue;
        }
        const value = record.normalized_params ? record.normalized_params.ef_search : null;
        if (value == null) {
          continue;
        }
        values.add(value);
      }
      return Array.from(values).sort((a, b) => b - a);
    },
    uploadRecords() {
      if (!this.vectorData || !this.vectorData.upload_records) {
        return [];
      }
      return this.vectorData.upload_records;
    },
    uploadLatestLookup() {
      if (!this.vectorData || !this.vectorData.upload_records) {
        return new Map();
      }
      const lookup = new Map();
      this.vectorData.upload_records.forEach(record => {
        const key = `${record.dataset}|${record.engine}|${record.experiment}`;
        const existing = lookup.get(key);
        if (!existing || (record.last_timestamp && record.last_timestamp > existing.last_timestamp)) {
          lookup.set(key, record);
        }
      });
      return lookup;
    },
    uploadTableRecords() {
      if (!this.dataset) {
        return [];
      }
      const selectedEngines = new Set(this.selectedEngines || []);
      const precisionLookup = this.maxPrecisionLookup;
      return this.uploadRecords.filter(record => {
        if (record.dataset !== this.dataset) {
          return false;
        }
        if (selectedEngines.size === 0) {
          return false;
        }
        if (!selectedEngines.has(record.engine)) {
          return false;
        }
        if (this.selectedParallel !== null && record.parallel !== this.selectedParallel) {
          return false;
        }
        if (this.selectedStorageEngine !== null) {
          const storageEngine = record.collection_params ? record.collection_params.engine : null;
          if (storageEngine !== this.selectedStorageEngine) {
            return false;
          }
        }
        if (this.selectedOptimizeCutoff !== null) {
          const cutoff = record.collection_params ? record.collection_params.optimize_cutoff : null;
          if (cutoff !== this.selectedOptimizeCutoff) {
            return false;
          }
        }
        if (this.selectedAutoOptimize !== null) {
          const autoOptimize = record.collection_params ? record.collection_params.auto_optimize : null;
          if (autoOptimize !== this.selectedAutoOptimize) {
            return false;
          }
        }
        if (this.selectedM !== null) {
          const value = record.normalized_params ? record.normalized_params.m : null;
          if (value !== this.selectedM) {
            return false;
          }
        }
        if (this.selectedEfBuild !== null) {
          const value = record.normalized_params ? record.normalized_params.ef_build : null;
          if (value !== this.selectedEfBuild) {
            return false;
          }
        }
        if (this.selectedEfSearch !== null) {
          const value = record.normalized_params ? record.normalized_params.ef_search : null;
          if (value !== this.selectedEfSearch) {
            return false;
          }
        }
        const key = `${record.dataset}|${record.engine}|${record.experiment}`;
        const precision = precisionLookup.get(key);
        if (precision == null) {
          return false;
        }
        return precision >= this.precisionThreshold;
      });
    },
    maxPrecisionLookup() {
      const lookup = new Map();
      for (const record of this.searchRecords) {
        const key = `${record.dataset}|${record.engine}|${record.experiment}`;
        const existing = lookup.get(key);
        if (existing === undefined || record.mean_precision > existing) {
          lookup.set(key, record.mean_precision);
        }
      }
      return lookup;
    },
    activeRecords() {
      if (!this.dataset) {
        return [];
      }
      const selectedEngines = new Set(this.selectedEngines || []);
      return this.searchRecords.filter(record => {
        if (record.dataset !== this.dataset) {
          return false;
        }
        if (selectedEngines.size === 0) {
          return false;
        }
        if (!selectedEngines.has(record.engine)) {
          return false;
        }
        if (!this.isIndexTime && this.selectedParallel !== null && record.parallel !== this.selectedParallel) {
          return false;
        }
        if (this.selectedStorageEngine !== null) {
          const storageEngine = record.collection_params ? record.collection_params.engine : null;
          if (storageEngine !== this.selectedStorageEngine) {
            return false;
          }
        }
        if (this.selectedOptimizeCutoff !== null) {
          const cutoff = record.collection_params ? record.collection_params.optimize_cutoff : null;
          if (cutoff !== this.selectedOptimizeCutoff) {
            return false;
          }
        }
        if (this.selectedAutoOptimize !== null) {
          const autoOptimize = record.collection_params ? record.collection_params.auto_optimize : null;
          if (autoOptimize !== this.selectedAutoOptimize) {
            return false;
          }
        }
        if (this.selectedM !== null) {
          const value = record.normalized_params ? record.normalized_params.m : null;
          if (value !== this.selectedM) {
            return false;
          }
        }
        if (this.selectedEfBuild !== null) {
          const value = record.normalized_params ? record.normalized_params.ef_build : null;
          if (value !== this.selectedEfBuild) {
            return false;
          }
        }
        if (this.selectedEfSearch !== null) {
          const value = record.normalized_params ? record.normalized_params.ef_search : null;
          if (value !== this.selectedEfSearch) {
            return false;
          }
        }
        return true;
      });
    },
    uploadLookup() {
      if (!this.vectorData || !this.vectorData.upload_records) {
        return new Map();
      }
      const lookup = new Map();
      this.vectorData.upload_records
          .forEach(record => {
            const key = `${record.dataset}|${record.engine}|${record.experiment}|${record.parallel}`;
            const existing = lookup.get(key);
            if (!existing || (record.last_timestamp && record.last_timestamp > existing.last_timestamp)) {
              lookup.set(key, record);
            }
          });
      return lookup;
    },
    filteredTableRecords() {
      return this.activeRecords.filter(record => record.mean_precision >= this.precisionThreshold);
    },
    sortedTableRecords() {
      const records = [...this.filteredTableRecords];
      const key = this.sortKey;
      const direction = this.sortDirection === 'desc' ? -1 : 1;
      records.sort((a, b) => {
        if (a[key] === b[key]) {
          return 0;
        }
        return a[key] > b[key] ? -direction : direction;
      });
      return records;
    },
    sortedUploadTableRecords() {
      const records = [...this.uploadTableRecords];
      const key = this.uploadSortKey;
      const direction = this.uploadSortDirection === 'desc' ? -1 : 1;
      records.sort((a, b) => {
        if (a[key] === b[key]) {
          return 0;
        }
        return a[key] > b[key] ? -direction : direction;
      });
      return records;
    }
  },
  watch: {
    activeRecords() {
      this.renderCharts();
    },
    precisionThreshold() {
      this.renderCharts();
      this.emitStateChange();
    },
    plotMetric() {
      this.renderCharts();
      this.emitStateChange();
      if (this.plotMetric === 'index_time') {
        this.uploadSortKey = 'upload_ms';
        this.uploadSortDirection = 'asc';
      }
    },
    selectedParallel() {
      this.renderCharts();
      this.emitStateChange();
    },
    selectedStorageEngine() {
      this.renderCharts();
      this.emitStateChange();
    },
    selectedOptimizeCutoff() {
      this.renderCharts();
      this.emitStateChange();
    },
    selectedAutoOptimize() {
      this.renderCharts();
      this.emitStateChange();
    },
    selectedM() {
      this.renderCharts();
      this.emitStateChange();
    },
    selectedEfBuild() {
      this.renderCharts();
      this.emitStateChange();
    },
    selectedEfSearch() {
      this.renderCharts();
      this.emitStateChange();
    },
    selectedEngines() {
      this.ensureParallelSelection();
      this.ensureParamSelection();
      this.renderCharts();
    },
    selectedDataset() {
      this.ensureParallelSelection();
      this.ensureParamSelection();
      this.renderCharts();
    },
    initialPrecision(value) {
      if (value !== undefined && value !== null) {
        this.precisionThreshold = value;
      }
    },
    initialPlotMetric(value) {
      if (value) {
        this.plotMetric = value;
      }
    },
    initialParallel(value) {
      if (value !== undefined && value !== null && value !== this.selectedParallel) {
        this.selectedParallel = value;
        this.ensureParallelSelection();
      }
    },
    initialStorageEngine(value) {
      if (value && value !== this.selectedStorageEngine) {
        this.selectedStorageEngine = value;
        this.ensureParamSelection();
      }
    },
    initialOptimizeCutoff(value) {
      if (value !== undefined && value !== null && value !== this.selectedOptimizeCutoff) {
        this.selectedOptimizeCutoff = value;
        this.ensureParamSelection();
      }
    },
    initialAutoOptimize(value) {
      if (value !== undefined && value !== null && value !== this.selectedAutoOptimize) {
        this.selectedAutoOptimize = value;
        this.ensureParamSelection();
      }
    },
    initialM(value) {
      if (value !== undefined && value !== null && value !== this.selectedM) {
        this.selectedM = value;
        this.ensureParamSelection();
      }
    },
    initialEfBuild(value) {
      if (value !== undefined && value !== null && value !== this.selectedEfBuild) {
        this.selectedEfBuild = value;
        this.ensureParamSelection();
      }
    },
    initialEfSearch(value) {
      if (value !== undefined && value !== null && value !== this.selectedEfSearch) {
        this.selectedEfSearch = value;
        this.ensureParamSelection();
      }
    },
    availableParallels() {
      this.ensureParallelSelection();
    },
    availableStorageEngines() {
      this.ensureParamSelection();
    },
    availableOptimizeCutoffs() {
      this.ensureParamSelection();
    },
    availableAutoOptimize() {
      this.ensureParamSelection();
    },
    availableMValues() {
      this.ensureParamSelection();
    },
    availableEfBuildValues() {
      this.ensureParamSelection();
    },
    availableEfSearchValues() {
      this.ensureParamSelection();
    }
  },
  mounted() {
    axios.get('/vector-data/index.json')
        .then(response => {
          this.vectorData = response.data;
          this.loading = false;
          if (this.vectorData && this.vectorData.datasets && this.vectorData.engines) {
            this.$emit('meta-ready', {
              datasets: this.vectorData.datasets,
              engines: this.vectorData.engines,
              dataset_engines: this.vectorData.dataset_engines
            });
          }
          this.$nextTick(() => {
            this.ensureParallelSelection();
            this.ensureParamSelection();
            this.initCharts();
            this.renderCharts();
          });
        })
        .catch(error => {
          this.loading = false;
          if (error.response && error.response.status === 404) {
            this.error = 'Missing vector-data/index.json in the built assets.';
          } else {
            this.error = 'Failed to load vector benchmark data.';
          }
        });
  },
  beforeDestroy() {
    if (this.scatterChart) {
      this.scatterChart.dispose();
    }
  },
  methods: {
    ensureParallelSelection() {
      if (!this.availableParallels.length) {
        this.selectedParallel = null;
        return;
      }
      if (this.selectedParallel === null || !this.availableParallels.includes(this.selectedParallel)) {
        this.selectedParallel = this.availableParallels[0];
      }
    },
    ensureParamSelection() {
      if (this.selectedStorageEngine !== null && !this.availableStorageEngines.includes(this.selectedStorageEngine)) {
        this.selectedStorageEngine = null;
      }

      if (this.selectedOptimizeCutoff !== null && !this.availableOptimizeCutoffs.includes(this.selectedOptimizeCutoff)) {
        this.selectedOptimizeCutoff = null;
      }

      if (this.selectedAutoOptimize !== null && !this.availableAutoOptimize.includes(this.selectedAutoOptimize)) {
        this.selectedAutoOptimize = null;
      }

      if (this.selectedM !== null && !this.availableMValues.includes(this.selectedM)) {
        this.selectedM = null;
      }

      if (this.selectedEfBuild !== null && !this.availableEfBuildValues.includes(this.selectedEfBuild)) {
        this.selectedEfBuild = null;
      }

      if (this.selectedEfSearch !== null && !this.availableEfSearchValues.includes(this.selectedEfSearch)) {
        this.selectedEfSearch = null;
      }
    },
    initCharts() {
      if (this.$refs.scatterChart && !this.scatterChart) {
        this.scatterChart = echarts.init(this.$refs.scatterChart);
      }
    },
    renderCharts() {
      if (!this.scatterChart) {
        return;
      }
      const records = this.activeRecords;
      const metricLabel = this.getMetricLabel();
      const seriesMap = new Map();
      const maxPrecisionBySeries = new Map();
      const precisionValues = [];
      const metricValues = [];
      if (this.plotMetric === 'index_time') {
        for (const record of records) {
          const seriesKey = record.engine;
          const currentMax = maxPrecisionBySeries.get(seriesKey);
          if (currentMax === undefined || record.mean_precision > currentMax) {
            maxPrecisionBySeries.set(seriesKey, record.mean_precision);
          }
        }
      }
      for (const record of records) {
        const seriesKey = record.engine;
        if (this.plotMetric === 'index_time' && maxPrecisionBySeries.get(seriesKey) !== record.mean_precision) {
          continue;
        }
        if (!seriesMap.has(seriesKey)) {
          seriesMap.set(seriesKey, []);
        }
        const value = this.getMetricValue(record);
        if (value === null || value === undefined) {
          continue;
        }
        precisionValues.push(record.mean_precision);
        metricValues.push(value);
        seriesMap.get(seriesKey).push({
          value: [record.mean_precision, value],
          record,
          itemStyle: {
            opacity: record.mean_precision < this.precisionThreshold ? 0.2 : 0.95
          }
        });
      }

      const series = Array.from(seriesMap.entries()).map(([name, data]) => {
        data.sort((a, b) => a.value[0] - b.value[0]);
        return {
          name,
          type: 'line',
          showSymbol: true,
          data
        };
      });
      if (metricValues.length > 0) {
        const rawMin = Math.min(...metricValues);
        const rawMax = Math.max(...metricValues);
        const range = rawMax - rawMin;
        const pad = range > 0 ? range * 0.05 : rawMax * 0.05;
        const yMin = Math.max(0, rawMin - pad);
        const yMax = rawMax + pad;
        series.push({
          name: 'Precision threshold',
          type: 'line',
          showSymbol: false,
          silent: true,
          lineStyle: {color: '#d9534f', width: 1},
          data: [
            {
              value: [this.precisionThreshold, yMin],
              label: { show: false }
            },
            {
              value: [this.precisionThreshold, yMax],
              label: {
                show: true,
                formatter: this.formatPrecision(this.precisionThreshold),
                position: 'end',
                color: '#d9534f'
              }
            }
          ]
        });
      }
      let minPrecision = 0;
      let maxPrecision = 1;
      if (precisionValues.length) {
        const rawMin = Math.min(...precisionValues);
        const rawMax = Math.max(...precisionValues);
        const range = rawMax - rawMin;
        const pad = range > 0 ? range * 0.05 : rawMin * 0.02;
        minPrecision = Math.max(0, rawMin - pad);
        maxPrecision = Math.min(1, rawMax + pad);
      }
      this.precisionBounds = { min: minPrecision, max: maxPrecision };
      const precisionRange = maxPrecision - minPrecision;
      if (precisionRange > 0) {
        const rawStep = precisionRange / 10;
        this.precisionStep = Number(rawStep.toFixed(4));
      } else {
        this.precisionStep = Number((minPrecision * 0.002).toFixed(4));
      }
      const clamped = Math.min(maxPrecision, Math.max(minPrecision, this.precisionThreshold));
      const stepsFromMin = this.precisionStep > 0 ? Math.round((clamped - minPrecision) / this.precisionStep) : 0;
      const snapped = minPrecision + stepsFromMin * this.precisionStep;
      this.precisionThreshold = Number(snapped.toFixed(4));

      const scatterOption = {
        tooltip: {
          trigger: 'item',
          formatter: params => {
            const record = params.data.record;
            return [
              `<div><strong>${record.engine}</strong> (${record.experiment})</div>`,
              `<div>Parallel: ${record.parallel}</div>`,
              `<div>Search index: ${this.formatOptional(record.searchIndex)}</div>`,
              `<div>Num candidates: ${this.formatOptional(record.num_candidates)}</div>`,
              `<div>Options:</div>${this.formatObjectList(record.options, true)}`,
              `<div>Index options:</div>${this.formatObjectList(record.collection_params, true)}`,
              `<div>RPS: ${this.formatRps(record.rps)}</div>`,
              `<div>Latency: ${this.formatMs(record.mean_ms)} ms</div>`,
              `<div>p95: ${this.formatMs(record.p95_ms)} ms</div>`,
              `<div>p99: ${this.formatMs(record.p99_ms)} ms</div>`,
              `<div>Mean precision: ${this.formatPrecision(record.mean_precision)}</div>`,
              `<div>Runs: ${record.runs}</div>`
            ].join('');
          }
        },
        legend: {
          type: 'scroll',
          top: 0
        },
        grid: {left: 50, right: 20, top: 40, bottom: 50},
        xAxis: {
          type: 'value',
          name: 'Precision',
          min: minPrecision,
          max: maxPrecision,
          interval: this.precisionStep,
          scale: true,
          axisLabel: {
            formatter: (value) => this.formatPrecision(value)
          }
        },
        yAxis: {
          type: 'value',
          name: metricLabel
        },
        series
      };
      this.scatterChart.setOption(scatterOption, true);

    },
    setSort(key) {
      if (key === 'p95_ms') {
        this.sortKey = key;
        this.sortDirection = 'asc';
        return;
      }
      if (key === 'mean_precision') {
        this.sortKey = key;
        this.sortDirection = 'desc';
        return;
      }
      if (key === 'rps') {
        if (this.sortKey === key) {
          this.sortDirection = this.sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
          this.sortKey = key;
          this.sortDirection = 'desc';
        }
      }
    },
    setUploadSort(key) {
      if (this.uploadSortKey === key) {
        this.uploadSortDirection = this.uploadSortDirection === 'desc' ? 'asc' : 'desc';
      } else {
        this.uploadSortKey = key;
        this.uploadSortDirection = 'asc';
      }
    },
    getMetricLabel() {
      switch (this.plotMetric) {
        case 'rps':
          return 'RPS';
        case 'latency':
          return 'Latency (ms)';
        case 'p95':
          return 'p95 latency (ms)';
        case 'p99':
          return 'p99 latency (ms)';
        case 'index_time':
          return 'Index time (ms)';
        default:
          return 'RPS';
      }
    },
    getMetricValue(record) {
      switch (this.plotMetric) {
        case 'rps':
          return record.rps;
        case 'latency':
          return record.mean_ms;
        case 'p95':
          return record.p95_ms;
        case 'p99':
          return record.p99_ms;
        case 'index_time': {
          if (this.selectedParallel === null) {
            return null;
          }
          const key = `${record.dataset}|${record.engine}|${record.experiment}|${this.selectedParallel}`;
          const upload = this.uploadLookup.get(key);
          return upload ? upload.upload_ms : null;
        }
        default:
          return record.rps;
      }
    },
    formatMs(value) {
      if (value == null) {
        return '-';
      }
      return Number(value).toFixed(2);
    },
    formatRps(value) {
      if (value == null) {
        return '-';
      }
      return Number(value).toFixed(1);
    },
    formatPrecision(value) {
      if (value == null) {
        return '-';
      }
      return String(value);
    },
    formatOptional(value, stringify = false) {
      if (value == null) {
        return '-';
      }
      if (stringify && typeof value === 'object') {
        return JSON.stringify(value);
      }
      return value;
    },
    getUploadTime(record) {
      if (this.isIndexTime) {
        if (this.selectedParallel === null) {
          return null;
        }
        const key = `${record.dataset}|${record.engine}|${record.experiment}|${this.selectedParallel}`;
        const upload = this.uploadLookup.get(key);
        return upload ? upload.upload_ms : null;
      }
      const key = `${record.dataset}|${record.engine}|${record.experiment}`;
      const upload = this.uploadLatestLookup.get(key);
      return upload ? upload.upload_ms : null;
    },
    escapeHtml(value) {
      return String(value)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#39;');
    },
    formatObjectList(value, inline = false) {
      if (value == null) {
        return inline ? '-' : '-';
      }
      const renderList = (input) => {
        const items = [];
        if (Array.isArray(input)) {
          input.forEach((item, index) => {
            if (item && typeof item === 'object') {
              items.push(`<li>${index}: ${renderList(item)}</li>`);
            } else {
              items.push(`<li>${index}: ${this.escapeHtml(item)}</li>`);
            }
          });
        } else if (input && typeof input === 'object') {
          Object.keys(input).sort().forEach((key) => {
            const entryValue = input[key];
            if (entryValue && typeof entryValue === 'object') {
              items.push(`<li>${this.escapeHtml(key)}: ${renderList(entryValue)}</li>`);
            } else {
              items.push(`<li>${this.escapeHtml(key)}: ${this.escapeHtml(entryValue)}</li>`);
            }
          });
        } else {
          items.push(`<li>${this.escapeHtml(input)}</li>`);
        }
        return `<ul style="margin: 0 0 0 16px; padding: 0;">${items.join('')}</ul>`;
      };
      return renderList(value);
    },
    emitStateChange() {
      this.$emit('state-change', {
        precision: this.precisionThreshold,
        plotMetric: this.plotMetric,
        parallel: this.selectedParallel,
        storageEngine: this.selectedStorageEngine,
        optimizeCutoff: this.selectedOptimizeCutoff,
        autoOptimize: this.selectedAutoOptimize,
        m: this.selectedM,
        efBuild: this.selectedEfBuild,
        efSearch: this.selectedEfSearch
      });
    }
  }
};
</script>

<style scoped>
.vector-chart {
  width: 100%;
  height: 360px;
}

.precision-control {
  margin-bottom: 24px;
}

.precision-control input[type='range'] {
  width: 100%;
}

.plot-controls {
  margin-bottom: 12px;
}

.precision-control--below {
  margin-top: 12px;
}
</style>
