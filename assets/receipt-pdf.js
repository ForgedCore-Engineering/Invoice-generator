// Shared branded generator for automatic downloads and re-downloads.
async function generatePDF(d) {
  // Create A4 doc with ForgedCore letterhead background
  const { doc, pw, ph, mg, safeTop, safeBottom, addPage } = await createLetterheadDoc();
  const cw = pw - mg * 2;
  let y = safeTop;
  // Keep flowing content above the footer note and printed letterhead slogan.
  const bodyBottom = safeBottom - 8;
  function reserve(height) {
    if (y + height > bodyBottom) {
      addPage();
      y = safeTop;
    }
  }
  function wrappedText(value, width = cw) {
    const lines = doc.splitTextToSize(String(value || ''), width);
    for (const line of lines) {
      reserve(5);
      doc.text(line, mg, y);
      y += 5;
    }
  }

  doc.setFont('times');

  // ── Document Title ──
  doc.setFontSize(20); doc.setFont('times', 'bold');
  doc.setTextColor(13, 46, 70);
  doc.text('RECEIPT', pw / 2, y, { align: 'center' });
  y += 7;

  // Teal underline accent
  doc.setDrawColor(0, 128, 115);
  doc.setLineWidth(0.6);
  doc.line(pw / 2 - 18, y, pw / 2 + 18, y);
  doc.setLineWidth(0.2);
  y += 6;

  // ── Invoice Meta (two-column) ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('INVOICE NO', mg, y);
  doc.text('DATE ISSUED', pw / 2 + 2, y);
  y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(13, 46, 70);
  doc.text(d.invoice_no, mg, y);
  doc.text(d.date, pw / 2 + 2, y);
  y += 6;

  // ── Bill To ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('BILL TO', mg, y); y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(20, 20, 20);
  wrappedText(d.name);
  doc.setFontSize(9.5); doc.setTextColor(80, 80, 80);
  wrappedText(d.address + '   |   ' + d.contact); y += 3;

  // Separator line
  doc.setDrawColor(200, 200, 200); doc.setLineWidth(0.3);
  doc.line(mg, y, pw - mg, y); y += 6;

  // ── Description ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  reserve(12);
  doc.text('SERVICE / DESCRIPTION', mg, y); y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(20, 20, 20);
  wrappedText(d.description);
  y += 5;

  // Separator line
  doc.setDrawColor(200, 200, 200); doc.setLineWidth(0.3);
  doc.line(mg, y, pw - mg, y); y += 6;

  // ── Payment Summary Table ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  reserve(81);
  doc.text('PAYMENT SUMMARY', mg, y); y += 7;

  const rh = 8, tTop = y;
  // Table header row
  doc.setFillColor(13, 46, 70);
  doc.roundedRect(mg, tTop, cw, rh, 1.5, 1.5, 'F');
  doc.setFont('times', 'bold'); doc.setFontSize(9); doc.setTextColor(255, 255, 255);
  doc.text('DESCRIPTION', mg + 4, tTop + 5.5);
  doc.text('AMOUNT (GHS)', pw - mg - 4, tTop + 5.5, { align: 'right' });

  const bal = d.total - d.paid;
  [
    { desc: 'Total Invoice Amount', amt: d.total.toFixed(2), color: null },
    { desc: 'Amount Paid',          amt: d.paid.toFixed(2),  color: [0, 128, 64] },
    { desc: 'Outstanding Balance',  amt: Math.max(0, bal).toFixed(2), color: bal > 0 ? [200, 30, 30] : [0, 128, 64] },
  ].forEach((row, i) => {
    const rY = tTop + rh + i * rh;
    doc.setFillColor(i % 2 === 0 ? 248 : 255, i % 2 === 0 ? 250 : 255, i % 2 === 0 ? 252 : 255);
    doc.rect(mg, rY, cw, rh, 'F');
    doc.setFont('times', 'normal'); doc.setFontSize(10); doc.setTextColor(30, 30, 30);
    doc.text(row.desc, mg + 4, rY + 5.5);
    if (row.color) { doc.setTextColor(...row.color); doc.setFont('times', 'bold'); }
    doc.text(row.amt, pw - mg - 4, rY + 5.5, { align: 'right' });
    doc.setTextColor(30, 30, 30);
  });
  // Table border
  doc.setDrawColor(210, 215, 220); doc.setLineWidth(0.3);
  doc.roundedRect(mg, tTop, cw, rh * 4, 1.5, 1.5, 'S');
  y = tTop + rh * 4 + 8;

  // ── Status Badge ──
  const status = bal <= 0 ? 'FULLY PAID' : (d.paid > 0 ? 'PARTIALLY PAID' : 'UNPAID');
  const [br, bg, bb] = bal <= 0 ? [220, 252, 231] : (d.paid > 0 ? [254, 243, 199] : [254, 226, 226]);
  const [tr, tg, tb] = bal <= 0 ? [21, 128, 61]  : (d.paid > 0 ? [146, 64, 14]  : [153, 27, 27]);
  doc.setFillColor(br, bg, bb);
  doc.roundedRect(mg, y, 58, 9, 2, 2, 'F');
  doc.setFont('times', 'bold'); doc.setFontSize(9); doc.setTextColor(tr, tg, tb);
  doc.text('STATUS: ' + status, mg + 4, y + 6);
  doc.setTextColor(0, 0, 0);
  // Place the signature alongside the status badge, in the right column.
  y += 3;

  // ── Signature ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(60, 60, 60);
  doc.text('Authorized Signature:', pw - mg - 68, y); y += 4;
  doc.setDrawColor(180, 180, 180); doc.setLineWidth(0.2);
  try {
    const si = await loadImg('receipts/static/signature.png');
    doc.addImage(si, 'PNG', pw - mg - 68, y, 55, 18); y += 22;
  } catch(e) { y += 14; }
  doc.setFont('times', 'normal'); doc.setFontSize(10); doc.setTextColor(20, 20, 20);
  doc.text('Eyram Dela Kuwornu', pw - mg - 68, y);
  doc.setFontSize(8.5); doc.setTextColor(100, 100, 100);
  doc.text('Director — Forgedcore Engineering Ltd', pw - mg - 68, y + 5);

  // ── Footer Note ──
  doc.setFont('times', 'italic'); doc.setFontSize(8); doc.setTextColor(140, 140, 140);
  doc.text('This document is computer generated and valid without stamp.', pw / 2, safeBottom - 2, { align: 'center' });

  doc.save('receipt_' + d.invoice_no.replace(/\//g, '_') + '.pdf');
}

function loadImg(url) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload  = () => resolve(img);
    img.onerror = () => reject(new Error('Not found'));
    img.src = url;
  });
}
