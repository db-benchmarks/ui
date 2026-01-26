# Vector Benchmarks

## Data locations
- Raw result files: `frontend/vector_results/*.json`
- Built index: `frontend/public/vector-data/index.json`

## Build the vector index
From `frontend/`:
```bash
npm run build:vector
```
This reads all JSON files under `frontend/vector_results/`, aggregates them, and writes the static index into `frontend/public/vector-data/index.json`.

## Local development
1) Generate the index (if missing):
```bash
npm run build:vector
```
2) Start the dev server:
```bash
npm run serve
```

The Vector view fetches `/vector-data/index.json` at runtime. If the file is missing, the UI shows an error with the build instructions.

## Adding new results
- Drop new JSON files into `frontend/vector_results/`.
- Re-run `npm run build:vector` before `npm run build` or `npm run serve`.
