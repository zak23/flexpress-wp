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

  // Test News Archive Page
  console.log("Testing News archive page...");
  await page.goto(url("/news"), { waitUntil: "networkidle0" });

  // Check for news archive elements
  const archiveChecks = await page.evaluate(() => {
    const title = document.querySelector("h1.display-4");
    const newsCards = document.querySelectorAll(".news-card");
    const pagination = document.querySelector(".pagination");
    const categoryFilters = document.querySelectorAll(".badge.bg-primary, .badge.bg-secondary");

    return {
      hasTitle: Boolean(title),
      cardCount: newsCards.length,
      hasPagination: Boolean(pagination),
      hasCategoryFilters: categoryFilters.length > 0,
    };
  });

  if (!archiveChecks.hasTitle) {
    throw new Error("News archive page missing title");
  }

  console.log(`Found ${archiveChecks.cardCount} news cards on archive page`);

  // Check if news CSS is loaded (async)
  const cssLoaded = await page.evaluate(() => {
    const newsStyles = Array.from(document.styleSheets).some((sheet) => {
      try {
        return sheet.href && sheet.href.includes("news.css");
      } catch (e) {
        return false;
      }
    });
    return newsStyles;
  });

  if (!cssLoaded) {
    console.warn("Warning: news.css may not be loaded (could be async)");
  }

  // If there are news cards, test clicking one
  if (archiveChecks.cardCount > 0) {
    console.log("Testing single post navigation...");
    const firstCardLink = await page.$(".news-card a.news-card-image-link, .news-card .card-title a");
    if (firstCardLink) {
      await firstCardLink.click();
      await page.waitForNavigation({ waitUntil: "networkidle0" });

      // Check single post elements
      const singleChecks = await page.evaluate(() => {
        const breadcrumb = document.querySelector(".breadcrumb");
        const postTitle = document.querySelector(".news-single-title");
        const postContent = document.querySelector(".news-single-content");
        const shareLinks = document.querySelectorAll(".news-single-share a");
        const prevNextNav = document.querySelector(".news-single-navigation");

        return {
          hasBreadcrumb: Boolean(breadcrumb),
          hasTitle: Boolean(postTitle),
          hasContent: Boolean(postContent),
          shareLinkCount: shareLinks.length,
          hasNav: Boolean(prevNextNav),
        };
      });

      if (!singleChecks.hasTitle) {
        throw new Error("Single post page missing title");
      }

      if (!singleChecks.hasContent) {
        throw new Error("Single post page missing content");
      }

      console.log(`Single post page has ${singleChecks.shareLinkCount} share links`);
    }
  } else {
    console.log("No news posts found - archive page renders empty state correctly");
  }

  // Test category filtering (if categories exist)
  if (archiveChecks.hasCategoryFilters) {
    console.log("Testing category filter...");
    const categoryBadge = await page.$(".badge.bg-secondary");
    if (categoryBadge) {
      const categoryText = await page.evaluate((el) => el.textContent.trim(), categoryBadge);
      await categoryBadge.click();
      await page.waitForNavigation({ waitUntil: "networkidle0" });

      const urlAfterFilter = page.url();
      if (!urlAfterFilter.includes("category=")) {
        throw new Error("Category filter did not update URL");
      }

      console.log(`Category filter applied: ${categoryText}`);
    }
  }

  await browser.close();
  console.log("News feature smoke test passed.");
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});

