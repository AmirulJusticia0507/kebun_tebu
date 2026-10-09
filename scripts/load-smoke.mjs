const target = process.argv[2] ?? "https://kebuntebu.vercel.app/";
const total = Number(process.argv[3] ?? 200);
const concurrency = Number(process.argv[4] ?? 100);

if (!Number.isInteger(total) || !Number.isInteger(concurrency) || total < 1 || concurrency < 1) {
  throw new Error("Usage: node scripts/load-smoke.mjs <url> <total> <concurrency>");
}

let cursor = 0;
let failures = 0;
const latencies = [];
const startedAt = performance.now();

async function worker() {
  while (cursor < total) {
    cursor += 1;
    const requestStartedAt = performance.now();

    try {
      const response = await fetch(target, {
        redirect: "follow",
        signal: AbortSignal.timeout(15_000),
        headers: { "User-Agent": "KebunTebu-Launch-Smoke/1.0" },
      });
      if (!response.ok) failures += 1;
      await response.arrayBuffer();
    } catch {
      failures += 1;
    } finally {
      latencies.push(performance.now() - requestStartedAt);
    }
  }
}

await Promise.all(Array.from({ length: Math.min(concurrency, total) }, worker));

latencies.sort((a, b) => a - b);
const percentile = (value) => latencies[Math.min(latencies.length - 1, Math.ceil(latencies.length * value) - 1)];
const durationMs = performance.now() - startedAt;
const errorRate = failures / total;

console.log(
  JSON.stringify(
    {
      target,
      total,
      concurrency,
      duration_ms: Math.round(durationMs),
      requests_per_second: Number((total / (durationMs / 1000)).toFixed(2)),
      p50_ms: Math.round(percentile(0.5)),
      p95_ms: Math.round(percentile(0.95)),
      failures,
      error_rate: Number(errorRate.toFixed(4)),
    },
    null,
    2,
  ),
);

if (errorRate > 0.01) process.exitCode = 1;
