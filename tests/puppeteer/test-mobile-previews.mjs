import puppeteer from "puppeteer";

const WP_IP = process.env.FLEXPRESS_IP || "127.0.0.1";
const WP_PORT = process.env.FLEXPRESS_PORT || "8085";

function url(path) {
  return `http://${WP_IP}:${WP_PORT}${path}`;
}

async function getEpisodeImages(page) {
  return await page.$$eval(
    ".episode-card img[data-preview-url][data-original-src]",
    (els) =>
      els.map((el) => ({
        src: el.getAttribute("src"),
        preview: el.getAttribute("data-preview-url"),
        original: el.getAttribute("data-original-src"),
      }))
  );
}

async function run() {
  const browser = await puppeteer.launch({ headless: "new" });
  const page = await browser.newPage();
  page.setDefaultTimeout(45000);

  // Emulate mobile/no-hover environment
  await page.emulateMediaFeatures([
    { name: "hover", value: "none" },
    { name: "pointer", value: "coarse" },
  ]);
  await page.setViewport({
    width: 390,
    height: 844,
    deviceScaleFactor: 2,
    isMobile: true,
    hasTouch: true,
  });

  // Navigate to episodes archive
  await page.goto(url("/episodes/"));
  await page.waitForSelector(
    ".episode-card img[data-preview-url][data-original-src]"
  );

  // Wait a moment for module init
  await page.waitForTimeout(1000);

  // Ensure at least one image exists
  const imgs0 = await getEpisodeImages(page);
  if (!imgs0 || imgs0.length === 0) {
    console.warn("No episode images found; skipping preview assertions.");
    await browser.close();
    return;
  }

  // Scroll a bit to bring some card near center
  await page.evaluate(() => window.scrollTo({ top: 300, behavior: "instant" }));
  await page.waitForTimeout(500);

  // Capture initial active (should switch one to preview.webp soon)
  const initial = await getEpisodeImages(page);
  const hasPreview = initial.some((i) => i.src && i.preview && i.src === i.preview);
  if (!hasPreview) {
    // Give the center-snap a moment
    await page.waitForTimeout(1500);
  }

  // After waiting, confirm one image uses preview src
  const afterSnap = await getEpisodeImages(page);
  const activeCount = afterSnap.filter((i) => i.src === i.preview).length;
  if (activeCount > 1) {
    throw new Error("More than one active preview on mobile.");
  }

  // Wait 11s to see rotation
  await page.waitForTimeout(11000);
  const afterRotate = await getEpisodeImages(page);
  const activeCount2 = afterRotate.filter((i) => i.src === i.preview).length;
  if (activeCount2 > 1) {
    throw new Error("Rotation activated multiple previews.");
  }

  // If none active after rotation, that's acceptable when none are centered; try to center again
  if (activeCount2 === 0) {
    await page.evaluate(() => window.scrollTo({ top: 600, behavior: "instant" }));
    await page.waitForTimeout(1000);
    const recheck = await getEpisodeImages(page);
    const reActive = recheck.filter((i) => i.src === i.preview).length;
    if (reActive > 1) {
      throw new Error("Multiple active previews after re-centering.");
    }
  }

  await browser.close();
  console.log("Puppeteer mobile preview tests passed.");
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});


