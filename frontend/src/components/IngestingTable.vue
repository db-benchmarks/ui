<script>
export default {
  name: "IngestingTable",
  props: {
    content: {
      required: true
    }
  },
  computed: {
    // Execution Time
    minLoadTime() {
      return Math.min(...this.content.map(row => this.getLoadTime(row)));
    },
    maxLoadTime() {
      return Math.max(...this.content.map(row => this.getLoadTime(row)));
    },
    measuredPostprocessTimes() {
      return this.content
          .map(row => this.getPostprocessTime(row))
          .filter(time => time !== null);
    },
    minPostprocessTime() {
      if (this.measuredPostprocessTimes.length === 0) {
        return null;
      }

      return Math.min(...this.measuredPostprocessTimes);
    },
    maxPostprocessTime() {
      if (this.measuredPostprocessTimes.length === 0) {
        return null;
      }

      return Math.max(...this.measuredPostprocessTimes);
    },
    // CPU
    minCPU95p() {
      return Math.min(...this.content.map(row => row.metrics.cpu["95p"]));
    },
    maxCPU95p() {
      return Math.max(...this.content.map(row => row.metrics.cpu["95p"]));
    },
    minCPUAvg() {
      return Math.min(...this.content.map(row => row.metrics.cpu.average));
    },
    maxCPUAvg() {
      return Math.max(...this.content.map(row => row.metrics.cpu.average));
    },
    minCPUMedian() {
      return Math.min(...this.content.map(row => row.metrics.cpu.median));
    },
    maxCPUMedian() {
      return Math.max(...this.content.map(row => row.metrics.cpu.median));
    },
    // RAM
    minRAM95p() {
      return Math.min(...this.content.map(row => row.metrics.ram["95p"]));
    },
    maxRAM95p() {
      return Math.max(...this.content.map(row => row.metrics.ram["95p"]));
    },
    minRAMAvg() {
      return Math.min(...this.content.map(row => row.metrics.ram.average));
    },
    maxRAMAvg() {
      return Math.max(...this.content.map(row => row.metrics.ram.average));
    },
    minRAMMedian() {
      return Math.min(...this.content.map(row => row.metrics.ram.median));
    },
    maxRAMMedian() {
      return Math.max(...this.content.map(row => row.metrics.ram.median));
    },
    // IO
    minIORead() {
      return Math.min(...this.content.map(row => row.metrics.disc.read?.total || Infinity));
    },
    maxIORead() {
      return Math.max(...this.content.map(row => row.metrics.disc.read?.total || -Infinity));
    },
    minIOWrite() {
      return Math.min(...this.content.map(row => row.metrics.disc.write?.total || Infinity));
    },
    maxIOWrite() {
      return Math.max(...this.content.map(row => row.metrics.disc.write?.total || -Infinity));
    }
  },
  methods: {
    getInitTime(row) {
      return parseInt(row.init_time, 10);
    },
    getPostprocessTime(row) {
      if (row.postprocess_time === undefined) {
        return null;
      }

      const postprocessTime = parseInt(row.postprocess_time, 10);
      return postprocessTime === -1 ? null : postprocessTime;
    },
    getLoadTime(row) {
      const initTime = this.getInitTime(row);
      const postprocessTime = this.getPostprocessTime(row);

      if (postprocessTime === null) {
        return initTime;
      }

      return initTime - postprocessTime;
    },
    formatHumanReadableTime: function (seconds) {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      const remainingSeconds = seconds % 60;
      let result = '';

      if (hours > 0) {
        result += hours + (hours === 1 ? ' hour ' : ' hours ');
      }
      if (minutes > 0) {
        result += minutes + (minutes === 1 ? ' min ' : ' mins ');
      }
      if (remainingSeconds > 0 || seconds === 0) {
        result += remainingSeconds + (remainingSeconds === 1 ? ' sec' : ' secs');
      }

      return result.trim();
    },
    formatHumanReadableMegaBytes: function (mb) {
      const units = ['MB', 'GB', 'TB'];
      let unitIndex = 0;
      while (mb >= 1024 && unitIndex < units.length - 1) {
        mb = mb / 1024;
        unitIndex++;
      }
      return mb.toFixed(2) + ' ' + units[unitIndex];
    }
  }
};
</script>

<template>
  <table class="table init-table">
    <tr>
      <th>Engine</th>
      <th v-tooltip="'Lower is better'">Load time</th>
      <th v-tooltip="'Lower is better'">Postprocess time</th>
      <th v-tooltip="'Lower is better'">CPU LOAD (cores)</th>
      <th v-tooltip="'Lower is better'">RAM</th>
      <th v-tooltip="'Lower is better'">IO (total)</th>
    </tr>

    <template v-for="(row, key) in content">
      <tr :key="key">
        <td>
          {{ row.engine_name }}{{ row.type.length > 0 ? "_" + row.type : "" }}:{{ row.version }}
        </td>

        <td>
          <span :class="{
            'text-green': getLoadTime(row) === minLoadTime,
            'text-red': getLoadTime(row) === maxLoadTime
          }">
            {{ formatHumanReadableTime(getLoadTime(row)) }}
          </span>
        </td>

        <td>
          <span v-if="getPostprocessTime(row) === null">-</span>
          <span
            v-else
            :class="{
              'text-green': getPostprocessTime(row) === minPostprocessTime,
              'text-red': getPostprocessTime(row) === maxPostprocessTime
            }"
          >
            {{ formatHumanReadableTime(getPostprocessTime(row)) }}
          </span>
        </td>

        <!-- CPU Load -->
        <td>
          <ul>
            <li :class="{
                'text-green': row.metrics.cpu.average === minCPUAvg,
                'text-red': row.metrics.cpu.average === maxCPUAvg
              }">
              <span class="avg">Average:</span>
                {{ row.metrics.cpu.average }}
            </li>
            <li :class="{
                'text-green': row.metrics.cpu.median === minCPUMedian,
                'text-red': row.metrics.cpu.median === maxCPUMedian
              }">
              <span class="median">Median:</span>
                {{ row.metrics.cpu.median }}
            </li>
            <li :class="{
              'text-green': row.metrics.cpu['95p'] === minCPU95p,
              'text-red': row.metrics.cpu['95p'] === maxCPU95p
            }">
              <span class="percentile">95 percentile:</span>
              <span>
                {{ row.metrics.cpu["95p"] }}
              </span>
            </li>
          </ul>
        </td>

        <!-- RAM Load -->
        <td>
          <ul>
            <li :class="{
                'text-green': row.metrics.ram.average === minRAMAvg,
                'text-red': row.metrics.ram.average === maxRAMAvg
              }">
              <span class="avg">Average:</span>
                {{ formatHumanReadableMegaBytes(row.metrics.ram.average) }}
            </li>
            <li :class="{
                'text-green': row.metrics.ram.median === minRAMMedian,
                'text-red': row.metrics.ram.median === maxRAMMedian
              }">
              <span class="median">Median:</span>

                {{ formatHumanReadableMegaBytes(row.metrics.ram.median) }}
            </li>
            <li :class="{
                'text-green': row.metrics.ram['95p'] === minRAM95p,
                'text-red': row.metrics.ram['95p'] === maxRAM95p
              }">
              <span class="percentile">95 percentile:</span>

                {{ formatHumanReadableMegaBytes(row.metrics.ram["95p"]) }}

            </li>
          </ul>
        </td>

        <!-- IO Read/Write -->
        <td>
          <ul>
            <template v-if="row.metrics.disc.read === undefined">
              <li><span class="avg">Total (r+w):</span> {{ formatHumanReadableMegaBytes(row.metrics.disc.total) }}</li>
            </template>
            <template v-else>
              <li :class="{
                  'text-green': row.metrics.disc.read.total === minIORead,
                  'text-red': row.metrics.disc.read.total === maxIORead
                }">
                <span class="avg">Read:</span>

                  {{ formatHumanReadableMegaBytes(row.metrics.disc.read.total) }}

              </li>
              <li :class="{
                  'text-green': row.metrics.disc.write.total === minIOWrite,
                  'text-red': row.metrics.disc.write.total === maxIOWrite
                }">
                <span class="avg">Write:</span>

                  {{ formatHumanReadableMegaBytes(row.metrics.disc.write.total) }}

              </li>
            </template>
          </ul>
        </td>
      </tr>
    </template>
  </table>
</template>

<style scoped>
.init-table td:not(:first-child) {
  border-left: 1px dotted #bbbbbb;
}

.init-table ul {
  padding-left: 15px;
}

.init-table .avg, .median, .percentile {
  font-size: small;
  color: #454545;
}

.text-red {
  color: red;
}

.text-green {
  color: green;
}
</style>
