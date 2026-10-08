// Playwright fetch script: render halaman dengan JavaScript lalu output HTML.
// Dipanggil oleh App\Crawlers\PlaywrightCrawler via CLI.
// Usage: node scripts/playwright_fetch.js <url>
const { chromium } = require('playwright');

(async () => {
  const url = process.argv[2];
  if (!url) {
    console.error('Usage: node playwright_fetch.js <url>');
    process.exit(1);
  }

  let browser;
  try {
    browser = await chromium.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
    const context = await browser.newContext({
      userAgent:
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
      locale: 'id-ID',
      viewport: { width: 1366, height: 900 },
    });

    // Blokir resource berat untuk hemat bandwidth
    await context.route('**/*', (route) => {
      const type = route.request().resourceType();
      if (['image', 'media', 'font'].includes(type)) {
        return route.abort();
      }
      return route.continue();
    });

    const page = await context.newPage();
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
    // Tunggu konten utama (jika lazy-load)
    await page.waitForTimeout(3000);

    const html = await page.content();
    console.log(JSON.stringify({ url, html }));
  } catch (err) {
    console.error(JSON.stringify({ error: err.message }));
    process.exit(1);
  } finally {
    if (browser) {
      await browser.close();
    }
  }
})();