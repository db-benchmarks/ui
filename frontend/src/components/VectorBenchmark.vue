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
          <div class="plot-controls">
            <label class="font-weight-bold">Plot values:</label>
            <div class="form-check form-check-inline" v-for="option in plotOptions" :key="option.value">
              <input class="form-check-input" type="radio" :id="`plot-${option.value}`"
                     :value="option.value" v-model="plotMetric">
              <label class="form-check-label" :for="`plot-${option.value}`">{{ option.label }}</label>
            </div>
          </div>
          <div ref="scatterChart" class="vector-chart"></div>
          <div class="precision-control precision-control--below">
            <label class="font-weight-bold">Precision threshold: {{ precisionThreshold.toFixed(4) }}</label>
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
          <h5>Search results</h5>
          <div v-if="filteredTableRecords.length === 0" class="alert alert-warning">
            No engines meet precision threshold — lower the slider.
          </div>
          <table v-else class="table table-sm">
            <thead>
            <tr>
              <th>Engine</th>
              <th>Experiment</th>
              <th>Parallel</th>
              <th>Num candidates</th>
              <th>Runs</th>
              <th role="button" @click="setSort('avg_rps')">Avg RPS</th>
              <th role="button" @click="setSort('avg_p95_ms')">Avg p95 (ms)</th>
              <th>Avg p99 (ms)</th>
              <th role="button" @click="setSort('mean_precision')">Precision</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="record in sortedTableRecords"
                :key="record.engine + '-' + record.experiment + '-' + record.parallel + '-' + record.num_candidates">
              <td>{{ record.engine }}</td>
              <td>{{ record.experiment }}</td>
              <td>{{ record.parallel }}</td>
              <td>{{ formatOptional(record.num_candidates) }}</td>
              <td>{{ record.runs }}</td>
              <td>{{ formatRps(record.avg_rps) }}</td>
              <td>{{ formatMs(record.avg_p95_ms) }}</td>
              <td>{{ formatMs(record.avg_p99_ms) }}</td>
              <td>{{ formatPrecision(record.mean_precision) }}</td>
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
    }
  },
  data() {
    return {
      loading: true,
      error: '',
      vectorData: null,
      precisionThreshold: this.initialPrecision !== undefined ? this.initialPrecision : 0.9,
      plotMetric: this.initialPlotMetric || 'rps',
      precisionBounds: { min: 0, max: 1 },
      precisionStep: 0.001,
      scatterChart: null,
      sortKey: 'avg_rps',
      sortDirection: 'desc'
    };
  },
  computed: {
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
      if (!this.vectorData || !this.vectorData.records) {
        return [];
      }
      return this.vectorData.records.filter(record => record.stage === 'search');
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
        return selectedEngines.has(record.engine);
      });
    },
    uploadLookup() {
      if (!this.vectorData || !this.vectorData.records) {
        return new Map();
      }
      const lookup = new Map();
      this.vectorData.records
          .filter(record => record.stage === 'upload')
          .forEach(record => {
            const key = `${record.dataset}|${record.engine}|${record.experiment}|${record.parallel}`;
            lookup.set(key, record);
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
    },
    selectedEngines() {
      this.renderCharts();
    },
    selectedDataset() {
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
              engines: this.vectorData.engines
            });
          }
          this.$nextTick(() => {
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
      const precisionValues = [];
      const metricValues = [];
      for (const record of records) {
        const seriesKey = `${record.engine} (${record.experiment})`;
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
                formatter: this.precisionThreshold.toFixed(4),
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
              `<strong>${record.engine}</strong> (${record.experiment})`,
              `Parallel: ${record.parallel}`,
              `Num candidates: ${this.formatOptional(record.num_candidates)}`,
              `Avg RPS: ${this.formatRps(record.avg_rps)}`,
              `Avg latency: ${this.formatMs(record.avg_mean_ms)} ms`,
              `Avg p95: ${this.formatMs(record.avg_p95_ms)} ms`,
              `Avg p99: ${this.formatMs(record.avg_p99_ms)} ms`,
              `Mean precision: ${this.formatPrecision(record.mean_precision)}`,
              `Runs: ${record.runs}`
            ].join('<br/>');
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
            formatter: (value) => value.toFixed(4)
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
      if (key === 'avg_p95_ms') {
        this.sortKey = key;
        this.sortDirection = 'asc';
        return;
      }
      if (key === 'mean_precision') {
        this.sortKey = key;
        this.sortDirection = 'desc';
        return;
      }
      if (key === 'avg_rps') {
        if (this.sortKey === key) {
          this.sortDirection = this.sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
          this.sortKey = key;
          this.sortDirection = 'desc';
        }
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
          return record.avg_rps;
        case 'latency':
          return record.avg_mean_ms;
        case 'p95':
          return record.avg_p95_ms;
        case 'p99':
          return record.avg_p99_ms;
        case 'index_time': {
          const key = `${record.dataset}|${record.engine}|${record.experiment}|${record.parallel}`;
          const upload = this.uploadLookup.get(key);
          return upload ? upload.avg_upload_ms : null;
        }
        default:
          return record.avg_rps;
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
      return Number(value).toFixed(3);
    },
    formatOptional(value) {
      if (value == null) {
        return '-';
      }
      return value;
    },
    emitStateChange() {
      this.$emit('state-change', {
        precision: this.precisionThreshold,
        plotMetric: this.plotMetric
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
