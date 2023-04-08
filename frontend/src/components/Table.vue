/* Copyright (C) 2023 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/
<template>
  <div>
    <hr>
    <h5>Relative query processing time (lower is better)</h5>
    <Bar v-bind:rows="rowSum.relative"
         v-bind:group-offset="groupedCount"/>
    <div class="container mt-3">
      <small>
        ❗Take into account that these final results are based on the below queries and can't be used as an
        <b>objective</b> metric for an advantage of one database over the others. It's provided for informational
        purposes only. To make it better fitting your workload it's recommended to uncheck the queries that you don't
        use.
      </small>
    </div>
    <hr>
    <h5>Full results</h5>
    <template v-if="results.length !== 0">
      <table class="table">
        <thead>
        <tr>
          <th rowspan="2" class="first-column">
            <div class="form-check">
              <input :indeterminate.prop="allQueryChecker.indeterminate"
                     :checked="allQueryChecker.checked" class="form-check-input" type="checkbox"
                     v-on:change="clickAllQuery"
                     id="flexCheckIndeterminate">
              <label class="form-check-label" for="flexCheckIndeterminate">Query</label>
            </div>
          </th>
          <template v-if="grouped">
            <th :class="classes.tableHeader" v-bind:colspan="groupedCount" scope="col" v-for="key in engines"
                :key="engines[key]">
              {{
                key.replaceAll(/(manticore)_(master|columnar)_(.*?)_(.*?)/g, 'Manticore $2 $3 $4').replaceAll('_', ' ')
              }}
              <DatasetInfoIcon
                  v-bind:engine="key"
                  v-on:showDatasetInfo="emitShowDatasetInfo"
              ></DatasetInfoIcon>
              <template v-if="hasRetest(key)">
                <RetestIcon
                    v-bind:engine="key"
                    v-on:showRetestResults="emitShowRetestResults"
                ></RetestIcon>
              </template>
            </th>
          </template>
          <template v-else>
            <th :class="classes.tableHeader" scope="col" v-for="key in engines" :key="engines[key]">
              {{
                key.replaceAll(/(manticore)_(master|columnar)_(.*?)_(.*?)/g, 'Manticore $2 $3 $4').replaceAll('_', ' ')
              }}
              <DatasetInfoIcon
                  v-bind:engine="key"
                  v-on:showDatasetInfo="emitShowDatasetInfo"
              ></DatasetInfoIcon>
              <template v-if="hasRetest(key)">
                <RetestIcon
                    v-bind:engine="key"
                    v-on:showRetestResults="emitShowRetestResults"
                ></RetestIcon>
              </template>
            </th>
          </template>
        </tr>

        <tr>
          <th scope="col" :class="classes.tableHeader" v-for="key in cache" :key="cache[key]">
            {{ key.replaceAll("_", " ").capitalize() }}
          </th>
        </tr>
        </thead>
        <tbody>

        <tr v-for="(index, row) in results" :key="results[index]">
          <td :class="classes.tableCeil"
              v-bind:style="{ 'background-color': getBackgroundColor(row, id), 'color':getTextColor(row, id) }"
              v-for="(key, id) in index" :key="key[index]">
            <div class="d-flex align-items-center">
              <template v-if="currentRelation=getRelation(row, id)">
                <template v-if="currentRelation!=1.00">
                  <span class="table-value flex-grow-1">x{{ currentRelation }}<span class="table-relation">&nbsp;({{
                      key
                    }} ms)</span></span>
                </template>
                <template v-else>
                  <span class="flex-grow-1 table-relation">{{ key }} ms</span>
                </template>
                <template v-if="checkCheckSum(row, id)">
                  &nbsp;<InfoIcon v-bind:row="row"
                                  v-bind:id="id"
                                  v-bind:hasDiff="true"
                                  v-on:showInfo="emitShowInfo"
                                  v-bind:class="checksumRelations[row][id]"
                                  v-bind:stroke-color="getTextColor(row, id)">
                </InfoIcon>
                </template>
                <template v-else>
                  <QuestionIcon v-bind:row="row"
                                v-bind:id="id"
                                v-bind:hasDiff="false"
                                v-on:showInfo="emitShowInfo"
                                v-bind:class="checksumRelations[row][id]"
                                v-bind:stroke-color="getTextColor(row, id)">
                  </QuestionIcon>
                </template>


              </template>
              <template v-else>
                <template v-if="id===0">
                  <span class="flex-grow-1">
                    <QuerySelector
                        v-bind:query="key"
                        v-bind:checked.sync="checkedQueries[row]"
                    />
                  </span>
                  <span>
                    <DiffIcon v-if="engines.length === 2 && checkedQueries[row] && checkCheckSum(row, id+1)"
                              v-bind:row="row"
                              v-on:showDiff="emitShowDiff">
                    </DiffIcon>
                </span>

                </template>
                <template v-else>
                  {{ key }}
                </template>
              </template>
            </div>
          </td>
        </tr>

        <tr>
          <td :class="classes.tableCeil">Arithmetic mean of ratios</td>
          <td :class="classes.tableCeil" v-for="(row, id) in rowSum.eachRow" :key="id">
            <template v-if="row.value!=0">x{{ row.value }}</template>
          </td>
        </tr>

        <tr>
          <td :class="classes.tableCeil">Geometric mean of ratios</td>
          <td :class="classes.tableCeil" v-for="(row, id) in rowSum.geomean" :key="id">
            <template v-if="row.value!=0">x{{ row.value }}</template>
          </td>
        </tr>

        <template v-if="this.grouped">
          <tr>
            <td :class="classes.tableCeil">Grouped</td>
            <td :class="classes.tableCeil" v-bind:colspan="groupedCount" v-for="(row, id) in rowSum.grouped"
                :key="id">
              <template v-if="row!=0">x{{ row }}</template>
            </td>
          </tr>
        </template>
        </tbody>
      </table>
    </template>
  </div>

</template>

<script>
import Bar from "@/components/Bar";
import QuerySelector from "@/components/QuerySelector";
import DiffIcon from "@/components/DiffIcon";
import InfoIcon from "@/components/InfoIcon";
import QuestionIcon from "@/components/QuestionIcon";
import DatasetInfoIcon from "./DatasetInfoIcon";
import RetestIcon from "./RetestIcon";

export default {
  components: {RetestIcon, DatasetInfoIcon, QuerySelector, Bar, DiffIcon, InfoIcon, QuestionIcon},
  props: {
    results: {
      type: Array,
      required: true
    },
    engines: {
      type: Array,
      required: true
    },
    cache: {
      type: Array,
      required: true
    },
    checkedQueries: {
      type: Array,
      required: true
    },
    checkSum: {
      type: Object,
      required: true
    },
    retestEngines: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      relations: [],
      circleColorIndex: 0,
      circleColors: ['orange', 'yellow', 'red', 'blue', 'green', 'purple', 'brown'],
      checksumRelations: {},
      colorsHelper: [],
      rowSum: {eachRow: {}, relative: {}, grouped: {}, geomean: {}},
      barNames: [],
      engineNames: [],
      grouped: false,
      classes: {
        tableHeader: "",
        tableCeil: ""
      },
      allQueryChecker: {
        checked: false,
        indeterminate: false
      }
    }
  },
  watch: {
    results() {
      this.grouped = this.checkIsGrouped();
      this.barNames = this.getBarNames();
      this.engineNames = this.getEngineNames();
      this.getChecksumRelations();
    },
    checkedQueries() {
      this.updateComputed();
      this.$emit('update:checked');
    },
    cache() {
      this.classes.tableHeader = ['align-top'];
      if (this.cache.length <= 2) {
        this.classes.tableHeader.push('th-4');
        this.classes.tableCeil = 'td-2';
      } else if (this.cache.length <= 4) {
        this.classes.tableHeader.push('th-4');
        this.classes.tableCeil = 'td-4';
      } else if (this.cache.length <= 6) {
        this.classes.tableHeader.push('th-6');
        this.classes.tableCeil = 'td-6';
      } else if (this.cache.length <= 8) {
        this.classes.tableHeader.push('th-8');
        this.classes.tableCeil = 'td-8';
      } else if (this.cache.length <= 12) {
        this.classes.tableHeader.push('th-12');
        this.classes.tableCeil = 'td-12';
      } else if (this.cache.length <= 16) {
        this.classes.tableHeader.push('th-16');
        this.classes.tableCeil = 'td-16';
      } else {
        this.classes.tableHeader.push('th-16');
        this.classes.tableCeil = 'td-16';
      }
    }
  },
  computed: {
    groupedCount: function () {
      return this.cache.length / this.engines.length;
    },
  },
  methods: {
    hasRetest(engine) {
      for (let engineKey in this.retestEngines) {
        if (Object.keys(this.retestEngines[engineKey]).includes(engine + "_retest")) {
          return true;
        }
      }
      return false;
    },
    emitShowDatasetInfo(engine) {
      this.$emit("showDatasetInfo", engine);
    },
    emitShowRetestResults(engine) {
      this.$emit("showRetestResults", engine);
    },
    emitShowInfo(row, id) {
      this.$emit('showInfo', row, id);
    },
    emitShowDiff(row) {
      this.$emit('showDiff', row);
    },
    onlyUnique: function (value, index, self) {
      return self.indexOf(value) === index;
    },

    clickAllQuery: function (event) {
      for (let index in this.checkedQueries) {
        this.checkedQueries[index] = event.target.checked;
      }
      this.computeAllQueryState();
      this.updateComputed();
      this.$emit('update:checked');
    },

    computeAllQueryState: function () {
      let filtered = this.checkedQueries.filter(this.onlyUnique);

      let indeterminate = false;
      if (filtered.length > 1) {
        indeterminate = true;
        filtered = [false];
      }
      this.allQueryChecker.indeterminate = indeterminate;
      this.allQueryChecker.checked = filtered[0];
    },
    updateComputed() {
      this.relations = this.computeStyles();
      this.rowSum = this.computeSums();
    },
    getChecksumRelations() {
      for (let rowId in this.results) {
        this.checksumRelations[rowId] = {};

        let circleColorPerGroup = {};

        let count = {};
        let halfQuorum = (Object.keys(this.checkSum[rowId]).length / 2);
        for (let checkSumKey in this.checkSum[rowId]) {
          if (count[this.checkSum[rowId][checkSumKey]] !== undefined) {
            count[this.checkSum[rowId][checkSumKey]]++;
          } else {
            count[this.checkSum[rowId][checkSumKey]] = 1;
          }
        }

        let quorumKey = null;
        for (let countKey in count) {
          circleColorPerGroup[countKey] = this.getCircleClass();
          if (count[countKey] > halfQuorum + 0.001) {
            quorumKey = countKey;
          }
        }

        for (let colId in this.results[rowId]) {

          // Skips query field
          if (parseInt(colId) === 0) {
            this.checksumRelations[rowId][colId] = null;
            continue;
          }

          let id = colId - 1;
          // get quorum

          if (Object.keys(this.checkSum[rowId]).length <= 1) {
            this.checksumRelations[rowId][colId] = null;
            continue;
          }

          let response = parseInt(quorumKey) !== this.checkSum[rowId][this.engineNames[id]];
          if (response) {
            this.checksumRelations[rowId][colId] = circleColorPerGroup[this.checkSum[rowId][this.engineNames[id]]];
          } else {
            this.checksumRelations[rowId][colId] = null;
          }
        }
      }
    },
    checkCheckSum(row, id) {
      return this.checksumRelations[row][id] !== null;
    },
    getCircleClass: function () {
      this.colorsHelper.push(this.circleColors[this.getCircleColorIndex()]);

      return this.colorsHelper[this.colorsHelper.length - 1];
    },
    getCircleColorIndex() {
      this.circleColorIndex++;

      if (this.circleColorIndex >= this.circleColors.length) {
        this.circleColorIndex = 0;
      }

      return this.circleColorIndex;
    },
    checkIsGrouped() {
      let grouped = false;

      if (this.cache[1] != null && this.cache[1] !== this.cache[0]) {
        grouped = true;
      }

      return grouped;
    },
    getBarNames() {
      let names = [];
      for (let cache in this.cache) {
        names.push(this.engines[parseInt(cache / this.groupedCount)]
            .replaceAll(/(manticore)_(master|columnar)_(.*?)_(.*?)/g, 'Manticore $2 $3 $4') + " (" + this.cache[cache].replaceAll("_", " ").capitalize() + ")");
      }
      return names
    },
    getEngineNames() {
      let names = [];
      for (let cache in this.cache) {
        names.push(this.engines[parseInt(cache / this.groupedCount)]);
      }
      return names
    },
    getTextColor(row, key) {
      if (this.relations[row] != null && this.relations[row][key] != null) {
        return this.relations[row][key].text_color;
      }
      return '';
    },
    getBackgroundColor(row, key) {
      if (this.relations[row] != null && this.relations[row][key] != null) {
        return this.relations[row][key].color;
      }
      return '';
    },
    getRelation(row, key) {
      if (this.relations[row] != null && this.relations[row][key] != null) {
        return this.relations[row][key].relation;
      }
      return false;
    },
    computeSums() {
      let rowSum = {eachRow: {}, relative: {}, grouped: {}, geomean: {}};

      let clearResultsLength = 0;
      this.relations.forEach((rowRelation, rowId) => {
        if (!this.hasFailsInRow(rowId)) {


          if (this.checkedQueries[rowId]) {
            clearResultsLength++;

            for (let ceilKey in rowRelation) {
              if (ceilKey == 'query') {
                continue;
              }


              if (rowSum.eachRow[ceilKey] === undefined) {
                rowSum.eachRow[ceilKey] = {value: 0};
              }

              if (rowSum.geomean[ceilKey] === undefined) {
                rowSum.geomean[ceilKey] = {value: 1};
              }

              rowSum.eachRow[ceilKey].value += parseFloat(rowRelation[ceilKey].relation);
              rowSum.geomean[ceilKey].value *= parseFloat(rowRelation[ceilKey].relation);

            }
          }
        }
      })


      let maxEachRowValue = Math.max(...Object.values(rowSum.eachRow).map(row => row.value));


      for (let row in rowSum.eachRow) {
        rowSum.eachRow[row]['percent'] = ((rowSum.eachRow[row]['value'] / maxEachRowValue) * 100).toFixed(2);
        rowSum.eachRow[row]['value'] = (rowSum.eachRow[row]['value'] / clearResultsLength).toFixed(2);

        rowSum.geomean[row]['value'] = (Math.pow(rowSum.geomean[row]['value'], 1 / clearResultsLength)).toFixed(2);
      }


      let maxGeomeanValue = Math.max(...Object.values(rowSum.geomean).map(row => row.value));
      for (let row in rowSum.eachRow) {
        if (maxGeomeanValue !== Infinity) {
          rowSum.geomean[row]['percent'] = ((rowSum.geomean[row]['value'] / maxGeomeanValue) * 100).toFixed(2);
        }
      }


      // Skip engine in bars in case if it has all results failed

      for (let engineKey in this.cache) {
        let skipped = 0, i = 0;
        engineKey = parseInt(engineKey);
        for (let rowId in this.results) {
          if (this.results[rowId][(engineKey + 1)] == '-') {
            skipped++;
            this.checkedQueries[rowId] = false;
          }
          i++;
        }
        if (skipped == i) {

          rowSum.geomean[engineKey + 1] = {
            percent: 0,
            value: 0
          };

          rowSum.eachRow[engineKey + 1] = {
            percent: 0,
            value: 0
          }
        }
      }

      this.computeAllQueryState()

      let maxValue = {}, minValue = {};


      for (let i in rowSum.geomean) {
        let engineId = this.getGroupByRow(i)
        maxValue[engineId] = 0;
        minValue[engineId] = 99999999;
      }


      let sumComputed = false;
      for (let id in rowSum.geomean) {

        let engineId = this.getGroupByRow(id)

        let val = parseFloat(rowSum.geomean[id].value);
        if (val !== 0 && val < minValue[engineId]) {
          minValue[engineId] = val;
          sumComputed = true;
        }

        if (val > maxValue[engineId]) {
          maxValue[engineId] = val
        }
      }

      if (sumComputed) {
        for (let row in rowSum.geomean) {
          let val = parseFloat(rowSum.geomean[row]['value']);

          let engineId = this.getGroupByRow(row)

          if (val === minValue[engineId]) {
            rowSum.relative[row] = {
              value: 1,
              percent: ((val / maxValue[engineId]) * 100).toFixed(2),
              engine: this.barNames[row - 1],
              type: this.cache[row - 1]

            }
          } else {
            rowSum.relative[row] = {
              value: (val / minValue[engineId]).toFixed(2),
              percent: ((val / maxValue[engineId]) * 100).toFixed(2),
              engine: this.barNames[row - 1],
              type: this.cache[row - 1]
            };
          }
        }
      }


      if (this.grouped) {

        for (let i in this.engines) {
          rowSum.grouped[i] = 0;
        }

        for (let i in rowSum.eachRow) {
          let forEngine = this.getEngineForRow(i - 2);
          rowSum.grouped[forEngine] += parseFloat(rowSum.eachRow[i]['value']);
        }

        for (let row in rowSum.grouped) {
          rowSum.grouped[row] = (rowSum.grouped[row] / this.groupedCount).toFixed(2)
        }
      }
      return rowSum;
    },
    computeStyles() {
      let results = [];

      for (let rowId in this.results) {
        let dataCols = [];
        let maxValue = {}, minValue = {};


        // get min and max values in row //
        for (let column in this.results[rowId]) {

          if (column != 0) {

            let byRow = this.getGroupByRow(column - 1);
            let value = this.results[rowId][column];

            if (maxValue[byRow] == undefined) {
              maxValue[byRow] = 0;
            }

            if (minValue[byRow] == undefined) {
              minValue[byRow] = 999999;
            }

            if (value > maxValue[byRow]) {
              maxValue[byRow] = value;
            }

            if (value <= minValue[byRow]) {
              minValue[byRow] = value;
            }

            dataCols.push({"column": column, "value": value})
          }
        }

        dataCols.forEach((row) => {
          let byRow = this.getGroupByRow(row.column - 1);
          if (row.value === '-') {
            row.value = maxValue[byRow]
          }
        })


        dataCols.sort((a, b) => (a.value > b.value) ? 1 : -1)


        let rowResults = {"query": {}};

        let skip = false;
        if (this.hasFailsInRow(rowId)) {
          skip = true;
        }

        dataCols.forEach((item) => {
          let obj = {}

          let byRow = this.getGroupByRow(item.column - 1);
          if (this.results[rowId][item.column] === '-') {
            obj = {color: "RGB(214,214,214)"};
          } else {


            let currentRelation = item.value / minValue[byRow]
            currentRelation = Math.round((currentRelation + Number.EPSILON) * 100) / 100

            obj['relation'] = currentRelation;

            if (skip) {
              obj['color'] = "RGB(214,214,214)"
            } else {
              if (currentRelation <= 1.03) {
                obj['color'] = "RGB(0, 255, 0)"; // green
              } else if (Math.abs(item.value - minValue[byRow]) <= 2 && minValue[byRow] >= 10 && minValue[byRow] <= 30) {
                obj['color'] = "RGB(0, 255, 0)"; // green
              } else if (Math.abs(item.value - minValue[byRow]) <= 1 && minValue[byRow] < 10) {
                obj['color'] = "RGB(0, 255, 0)"; // green
              } else if (currentRelation <= 1.05) {
                obj['color'] = "RGB(255, 0, 0, 0.5)"; // pink
              } else if (currentRelation <= 5) {
                obj['color'] = "RGB(255, 0, 0, " + (currentRelation * 0.9) + ")"; // red
              } else if (currentRelation > 5) {
                obj['color'] = "RGB(139, 0, 0)"; // darkred
              }
            }
          }

          obj['text_color'] = this.contrast(obj.color);
          rowResults[item.column] = obj;
        });

        results.push(rowResults);
      }
      return results
    },
    contrast(rgb) {
      rgb = rgb.split(/\(([^)]+)\)/)[1].replace(/ /g, '');

      var r = parseInt(rgb.split(',')[0], 10),
          g = parseInt(rgb.split(',')[1], 10),
          b = parseInt(rgb.split(',')[2], 10);

      var contrast = (Math.round(r * 299) + Math.round(g * 587) + Math.round(b * 114)) / 1000;

      return (contrast >= 128) ? 'black' : 'white';
    },
    getEngineForRow(id) {
      return parseInt((parseInt(id) + 1) / this.groupedCount)
    },
    getGroupByRow(id) {
      return id % this.groupedCount;
    },
    hasFailsInRow(rowId) {
      return this.results[rowId].includes('-');
    }
  }
}
</script>

<style>
.table th, .table td {
  padding: 0.35rem !important;
}

.table td:first-of-type {
  white-space: normal !important;
}

.table thead {
  background-color: #fff;
  position: sticky;
  top: 0;
  border-bottom: #7e7e7e 2px solid;
}

.table thead th {
  border: none !important;
}

.first-column {
  width: 90%;
}

.table-relation {
  font-size: smaller;
}

.table-value {
  font-weight: bold;
}

h5 {
  color: #227596;
  margin-left: 15px;
  margin-top: 35px !important;
  font-weight: bold !important;
}

.table-relation {
  font-weight: normal;
}

.th-normal {
  font-size: medium;
}

.th-6 {
  white-space: normal;
}

.th-8 {
  font-size: small;
}

.th-10, .th-12, .th-16 {
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 65px;
  font-size: 0.75vw;
}

.th-12 {
  max-width: 60px;
  font-size: x-small;
}

.th-16 {
  max-width: 15px;
  font-size: xx-small;
}

.td-2 {
  width: 30%;
  white-space: nowrap;
}

.td-4 {
  width: 20%;
  white-space: nowrap;
}

.td-6 {
  width: 15%;
  white-space: nowrap;
}

.td-8 {
  width: 15%;
  white-space: normal;
  font-size: smaller;
}

.td-12 {
  width: 10%;
  white-space: normal;
  font-size: small;
}

.td-16 {
  width: 10%;
  white-space: normal;
  font-size: small;
}

.td-12 .table-relation {
  display: none;
}

.td-16 .table-relation {
  display: none;
}

.td-16 .table-value {
  display: none;
}

div .table-relation {
  font-size: medium;
}

.table-value .table-relation {
  font-size: small;
}
</style>
