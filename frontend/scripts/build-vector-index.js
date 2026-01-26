#!/usr/bin/env node
/* Copyright (C) 2023 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */

const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');
const inputDir = path.join(rootDir, 'vector_results');
const outputDir = path.join(rootDir, 'public', 'vector-data');
const outputFile = path.join(outputDir, 'index.json');

function parseFilenameMeta(filename) {
  const base = path.basename(filename, '.json');
  const parts = base.split('-');
  if (parts.length < 8) {
    return { stage: null, searchIndex: null, lastTimestamp: null };
  }
  const timeParts = parts.slice(-6);
  if (!timeParts.every((part) => /^\d+$/.test(part))) {
    return { stage: null, searchIndex: null, lastTimestamp: null };
  }
  const remaining = parts.slice(0, -6);
  let stage = null;
  let searchIndex = null;
  if (remaining.length > 0) {
    const last = remaining[remaining.length - 1];
    const secondLast = remaining.length > 1 ? remaining[remaining.length - 2] : null;
    if (secondLast && /^\d+$/.test(last)) {
      searchIndex = Number(last);
      stage = secondLast;
    } else {
      stage = last;
    }
  }
  const [year, month, day, hour, minute, second] = timeParts.map(Number);
  const timestamp = new Date(Date.UTC(year, month - 1, day, hour, minute, second));
  const lastTimestamp = Number.isNaN(timestamp.getTime()) ? null : timestamp.toISOString();
  return { stage, searchIndex, lastTimestamp };
}

function getNumCandidates(params) {
  if (!params) {
    return null;
  }
  if (params.num_candidates !== undefined) {
    return params.num_candidates;
  }
  if (params.search_params) {
    if (params.search_params.num_candidates !== undefined) {
      return params.search_params.num_candidates;
    }
    if (params.search_params.hnsw_ef !== undefined) {
      return params.search_params.hnsw_ef;
    }
  }
  return null;
}

function main() {
  if (!fs.existsSync(inputDir)) {
    console.error(`Missing input directory: ${inputDir}`);
    process.exit(1);
  }

  const files = fs.readdirSync(inputDir).filter((file) => file.endsWith('.json'));
  if (files.length === 0) {
    console.error('No vector result files found in frontend/vector_results.');
    process.exit(1);
  }

  const aggregates = new Map();
  const datasets = new Set();
  const engines = new Set();

  for (const file of files) {
    const filePath = path.join(inputDir, file);
    let parsed;
    try {
      parsed = JSON.parse(fs.readFileSync(filePath, 'utf8'));
    } catch (error) {
      console.warn(`Skipping invalid JSON file: ${file}`);
      continue;
    }

    const params = parsed.params || {};
    const results = parsed.results || {};

    const dataset = params.dataset;
    const engine = params.engine;
    const experiment = params.experiment;

    if (!dataset || !engine || !experiment) {
      console.warn(`Skipping file with missing params: ${file}`);
      continue;
    }

    const { stage, searchIndex, lastTimestamp } = parseFilenameMeta(file);
    const parallel = params.parallel !== undefined ? params.parallel : null;
    const numCandidates = getNumCandidates(params);

    if (stage === 'upload') {
      if (results.upload_time === undefined && results.total_time === undefined) {
        console.warn(`Skipping upload file with missing results: ${file}`);
        continue;
      }
    } else {
      if (results.rps === undefined || results.mean_precisions === undefined || results.p95_time === undefined || results.p99_time === undefined) {
        console.warn(`Skipping search file with missing results: ${file}`);
        continue;
      }
    }

    datasets.add(dataset);
    engines.add(engine);

    const keyParts = [
      dataset,
      engine,
      experiment,
      stage || '',
      searchIndex !== null && searchIndex !== undefined ? String(searchIndex) : '',
      parallel !== null && parallel !== undefined ? String(parallel) : '',
      numCandidates !== null && numCandidates !== undefined ? String(numCandidates) : ''
    ];
    const key = keyParts.join('|');

    if (!aggregates.has(key)) {
      aggregates.set(key, {
        dataset,
        engine,
        experiment,
        stage: stage || 'search',
        searchIndex: searchIndex !== null ? searchIndex : null,
        parallel: parallel !== null ? parallel : null,
        num_candidates: numCandidates !== null ? numCandidates : null,
        runs: 0,
        sum_rps: 0,
        sum_precision: 0,
        sum_p95_ms: 0,
        sum_p99_ms: 0,
        sum_mean_ms: 0,
        count_mean_ms: 0,
        sum_upload_ms: 0,
        sum_total_upload_ms: 0,
        last_timestamp: lastTimestamp
      });
    }

    const entry = aggregates.get(key);
    entry.runs += 1;
    if (stage === 'upload') {
      if (results.upload_time !== undefined) {
        entry.sum_upload_ms += results.upload_time * 1000;
      }
      if (results.total_time !== undefined) {
        entry.sum_total_upload_ms += results.total_time * 1000;
      }
    } else {
      entry.sum_rps += results.rps;
      entry.sum_precision += results.mean_precisions;
      entry.sum_p95_ms += results.p95_time * 1000;
      entry.sum_p99_ms += results.p99_time * 1000;
      if (results.mean_time !== undefined) {
        entry.sum_mean_ms += results.mean_time * 1000;
        entry.count_mean_ms += 1;
      }
    }

    if (lastTimestamp && (!entry.last_timestamp || lastTimestamp > entry.last_timestamp)) {
      entry.last_timestamp = lastTimestamp;
    }
  }

  const records = Array.from(aggregates.values()).map((entry) => {
    const runs = entry.runs || 1;
    const meanCount = entry.count_mean_ms || 0;
    return {
      dataset: entry.dataset,
      engine: entry.engine,
      experiment: entry.experiment,
      stage: entry.stage,
      searchIndex: entry.searchIndex,
      parallel: entry.parallel,
      num_candidates: entry.num_candidates,
      runs: entry.runs,
      avg_rps: entry.stage === 'upload' ? null : entry.sum_rps / runs,
      mean_precision: entry.stage === 'upload' ? null : entry.sum_precision / runs,
      avg_p95_ms: entry.stage === 'upload' ? null : entry.sum_p95_ms / runs,
      avg_p99_ms: entry.stage === 'upload' ? null : entry.sum_p99_ms / runs,
      avg_mean_ms: entry.stage === 'upload' ? null : (meanCount > 0 ? entry.sum_mean_ms / meanCount : null),
      avg_upload_ms: entry.stage === 'upload' ? entry.sum_upload_ms / runs : null,
      avg_total_upload_ms: entry.stage === 'upload' ? entry.sum_total_upload_ms / runs : null,
      last_timestamp: entry.last_timestamp
    };
  });

  records.sort((a, b) => {
    const keys = ['dataset', 'engine', 'experiment', 'stage'];
    for (const key of keys) {
      if (a[key] !== b[key]) {
        return String(a[key]).localeCompare(String(b[key]));
      }
    }
    const numericKeys = ['searchIndex', 'parallel', 'num_candidates'];
    for (const key of numericKeys) {
      const aVal = a[key] === null ? -1 : a[key];
      const bVal = b[key] === null ? -1 : b[key];
      if (aVal !== bVal) {
        return aVal - bVal;
      }
    }
    return 0;
  });

  const output = {
    generated_at: new Date().toISOString(),
    datasets: Array.from(datasets).sort(),
    engines: Array.from(engines).sort(),
    records
  };

  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(outputFile, JSON.stringify(output, null, 2));
  console.log(`Vector index written to ${outputFile}`);
}

main();
