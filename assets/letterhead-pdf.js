/**
 * letterhead-pdf.js
 * Shared helper for generating branded ForgedCore Engineering PDFs.
 *
 * Usage:
 *   const { doc, pw, ph, mg, safeTop, safeBottom } = await createLetterheadDoc();
 *   // write your content starting at y = safeTop
 *   doc.save('filename.pdf');
 *
 * Safe content area (A4 mm):
 *   safeTop    = 58   — below the logo / header band
 *   safeBottom = 265  — above the teal footer contact strip
 *   mg (side)  = 20   — left & right margin
 */

/**
 * Pre-loads an image URL and returns a Promise resolving to an HTMLImageElement.
 * @param {string} url
 * @returns {Promise<HTMLImageElement>}
 */
function loadLetterheadImage(url) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('Could not load letterhead: ' + url));
    img.src = url;
  });
}

/**
 * Creates a jsPDF document pre-loaded with the ForgedCore letterhead background.
 *
 * @param {string} [letterheadUrl='assets/letterhead.png']  Path to the letterhead PNG.
 * @returns {Promise<{
 *   doc: jsPDF,
 *   pw: number,   // page width  (mm) = 210
 *   ph: number,   // page height (mm) = 297
 *   mg: number,   // side margin (mm) = 20
 *   safeTop: number,    // first usable Y (mm) = 58
 *   safeBottom: number  // last  usable Y (mm) = 265
 * }>}
 */
async function createLetterheadDoc(letterheadUrl = 'assets/letterhead.png') {
  const { jsPDF } = window.jspdf;

  const doc = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });

  const pw = doc.internal.pageSize.getWidth();   // 210
  const ph = doc.internal.pageSize.getHeight();  // 297

  // Draw the letterhead as a full-page background image
  try {
    const img = await loadLetterheadImage(letterheadUrl);
    doc.addImage(img, 'PNG', 0, 0, pw, ph);
  } catch (e) {
    // If letterhead fails to load, continue without it (graceful degradation)
    console.warn('[letterhead-pdf] Background not loaded:', e.message);
  }

  return {
    doc,
    pw,
    ph,
    mg: 20,         // side margin
    safeTop: 58,    // below logo / decorative header band
    safeBottom: 265 // above teal footer strip
  };
}
