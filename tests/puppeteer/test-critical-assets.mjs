import puppeteer from "puppeteer";

const WP_IP = process.env.FLEXPRESS_IP || "127.0.0.1";
const WP_PORT = process.env.FLEXPRESS_PORT || "8085";
const PUPPETEER_LAUNCH_CONFIG = {
  headless: "new",
  args: ["--no-sandbox", "--disable-setuid-sandbox"],
};

function url(path) {
  return `http://${WP_IP}:${WP_PORT}${path}`;
}

async function run() {
  const browser = await puppeteer.launch(PUPPETEER_LAUNCH_CONFIG);
  const page = await browser.newPage();
  page.setDefaultTimeout(30000);

  await page.evaluateOnNewDocument(() => {
    window.__domContentLoadedAt = null;
    window.__jQueryDefinedAt = null;

    document.addEventListener("DOMContentLoaded", () => {
      window.__domContentLoadedAt = performance.now();
    });

    const capture = (prop) => {
      let internalValue;
      Object.defineProperty(window, prop, {
        configurable: true,
        get() {
          return internalValue;
        },
        set(value) {
          internalValue = value;
          if (!window.__jQueryDefinedAt && typeof performance !== "undefined") {
            window.__jQueryDefinedAt = performance.now();
          }
        },
      });
    };

    capture("jQuery");
    capture("$");
  });

  await page.goto(url("/"), { waitUntil: "networkidle0" });

  const styleMetrics = await page.evaluate(() => {
    const preloadLinks = Array.from(
      document.querySelectorAll('link[rel="preload"][as="style"]')
    );
    const printSwapLinks = Array.from(
      document.querySelectorAll('link[rel="stylesheet"][media="print"]')
    ).filter((link) => {
      const onload = link.getAttribute("onload") || "";
      return onload.includes("this.media");
    });
    const noscriptFallbacks = Array.from(
      document.querySelectorAll('noscript link[rel="stylesheet"]')
    );
    const criticalInline = document.getElementById(
      "flexpress-critical-inline-inline-css"
    );

    return {
      preloadCount: preloadLinks.length,
      printSwapCount: printSwapLinks.length,
      noscriptCount: noscriptFallbacks.length,
      hasCriticalInline: Boolean(criticalInline),
    };
  });

  if (styleMetrics.preloadCount === 0) {
    throw new Error("Expected at least one stylesheet preload link.");
  }

  if (styleMetrics.printSwapCount === 0) {
    throw new Error("Expected at least one media=print swap stylesheet.");
  }

  if (styleMetrics.noscriptCount === 0) {
    throw new Error("Expected a noscript stylesheet fallback.");
  }

  if (!styleMetrics.hasCriticalInline) {
    throw new Error("Critical inline CSS not found.");
  }

  const jqueryInfo = await page.evaluate(() => {
    const script = document.querySelector('script[src*="jquery.min.js"]');
    if (!script) {
      return null;
    }

    return {
      defer: script.defer,
      parentTag: script.parentElement ? script.parentElement.tagName : null,
      inHead: document.head.contains(script),
    };
  });

  if (!jqueryInfo) {
    throw new Error("jQuery script tag not found.");
  }

  if (!jqueryInfo.defer) {
    throw new Error("jQuery script should be deferred.");
  }

  if (jqueryInfo.inHead || jqueryInfo.parentTag !== "BODY") {
    throw new Error("jQuery script should be appended to the document body.");
  }

  const timings = await page.evaluate(() => ({
    domContentLoadedAt: window.__domContentLoadedAt,
    jQueryDefinedAt: window.__jQueryDefinedAt,
  }));

  if (!timings.jQueryDefinedAt) {
    throw new Error("Unable to determine when jQuery was defined.");
  }

  await browser.close();
  console.log("Critical asset async smoke test passed.");
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});

