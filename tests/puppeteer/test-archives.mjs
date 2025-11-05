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

async function getFilterTexts(page, selector) {
  return await page.$$eval(selector, (els) =>
    els.map((e) => e.textContent.trim()).filter(Boolean)
  );
}

async function run() {
  const browser = await puppeteer.launch(PUPPETEER_LAUNCH_CONFIG);
  const page = await browser.newPage();
  page.setDefaultTimeout(30000);

  // Emulate a mobile device (hover: none, coarse pointer)
  try {
    await page.emulateMediaFeatures([
      { name: "hover", value: "none" },
      { name: "pointer", value: "coarse" },
    ]);
  } catch (err) {
    console.warn(`Media feature emulation skipped: ${err.message}`);
  }
  await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 2, isMobile: true, hasTouch: true });

  // Episodes: ensure category filter shows at least one tag and items have counts
  await page.goto(url("/episodes/"));
  await page.waitForSelector("#category-filters");
  const epTags = await getFilterTexts(page, "#category-filters .filter-item");
  if (epTags.length === 0) {
    throw new Error("Episodes: No category filter items found");
  }
  if (!epTags.some((t) => /\(\d+\)/.test(t))) {
    throw new Error("Episodes: No tag shows a numeric count");
  }

  // Models: ensure category filter shows only model-used tags with counts
  await page.goto(url("/models/"));
  await page.waitForSelector("#category-filters");
  const modelTags = await getFilterTexts(
    page,
    "#category-filters .filter-item"
  );
  if (modelTags.length === 0) {
    console.warn(
      "Models: No tags visible; skipping assert (may be valid if no model tags set)."
    );
  } else if (!modelTags.some((t) => /\(\d+\)/.test(t))) {
    throw new Error("Models: No tag shows a numeric count");
  }

  // Extras: ensure category filter shows extras-only tags with counts
  await page.goto(url("/extras/"));
  await page.waitForSelector("#category-filters");
  const extrasTags = await getFilterTexts(
    page,
    "#category-filters .filter-item"
  );
  if (extrasTags.length === 0) {
    console.warn(
      "Extras: No tags visible; skipping assert (may be valid if no extras tags set)."
    );
  } else if (!extrasTags.some((t) => /\(\d+\)/.test(t))) {
    throw new Error("Extras: No tag shows a numeric count");
  }

  await browser.close();
  console.log("Puppeteer archive filter smoke tests passed.");
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});

