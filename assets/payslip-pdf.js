// Shared branded generator for automatic downloads and re-downloads.
async function generatePDF(d) {
  // Create A4 doc with ForgedCore letterhead background
  const { doc, pw, ph, mg, safeTop, safeBottom } = await createLetterheadDoc();
  const cw = pw - mg * 2;
  let y = safeTop;
  const amountLeft = Math.max(0, Number(d.amount_due) - Number(d.amount_paid));
  const status = amountLeft <= 0 ? 'FULLY PAID' : (Number(d.amount_paid) > 0 ? 'PARTIALLY PAID' : 'UNPAID');

  doc.setFont('times');

  // ── Document Title ──
  doc.setFontSize(20); doc.setFont('times', 'bold');
  doc.setTextColor(13, 46, 70);
  doc.text('PAYSLIP', pw / 2, y, { align: 'center' });
  y += 7;

  // Teal underline accent
  doc.setDrawColor(0, 128, 115);
  doc.setLineWidth(0.6);
  doc.line(pw / 2 - 16, y, pw / 2 + 16, y);
  doc.setLineWidth(0.2);
  y += 10;

  // Sub-label
  doc.setFontSize(9); doc.setFont('times', 'italic'); doc.setTextColor(100, 100, 100);
  doc.text('Official Payment Record', pw / 2, y, { align: 'center' });
  y += 12;

  // ── Payslip Meta (two-column) ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('PAYSLIP NO', mg, y);
  doc.text('ISSUE DATE', pw / 2 + 2, y);
  y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(13, 46, 70);
  doc.text(d.payslip_no, mg, y);
  doc.text(d.issue_date, pw / 2 + 2, y);
  y += 12;

  // ── Employee Name ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('FULL NAME', mg, y); y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(20, 20, 20);
  doc.text(d.full_name, mg, y); y += 12;

  // Separator line
  doc.setDrawColor(200, 200, 200); doc.setLineWidth(0.3);
  doc.line(mg, y, pw - mg, y); y += 9;

  // ── Services ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('SERVICES', mg, y); y += 5;
  doc.setFont('times', 'normal'); doc.setFontSize(10.5); doc.setTextColor(20, 20, 20);
  const serviceLines = doc.splitTextToSize(d.service, cw);
  doc.text(serviceLines, mg, y);
  y += serviceLines.length * 6 + 10;

  // Separator line
  doc.setDrawColor(200, 200, 200); doc.setLineWidth(0.3);
  doc.line(mg, y, pw - mg, y); y += 9;

  // ── Payment Table ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(80, 80, 80);
  doc.text('PAYMENT SUMMARY', mg, y); y += 7;

  const rh = 10, tTop = y;
  // Table header row
  doc.setFillColor(13, 46, 70);
  doc.roundedRect(mg, tTop, cw, rh, 1.5, 1.5, 'F');
  doc.setFont('times', 'bold'); doc.setFontSize(9); doc.setTextColor(255, 255, 255);
  doc.text('DESCRIPTION', mg + 4, tTop + 6.5);
  doc.text('AMOUNT (GHS)', pw - mg - 4, tTop + 6.5, { align: 'right' });

  [
    { desc: 'Amount Supposed To Be Paid', amt: Number(d.amount_due).toFixed(2),  color: null },
    { desc: 'Amount Paid',                amt: Number(d.amount_paid).toFixed(2), color: [0, 128, 64] },
    { desc: 'Amount Left To Pay',         amt: Number(amountLeft).toFixed(2),   color: amountLeft > 0 ? [200, 30, 30] : [0, 128, 64] },
  ].forEach((row, i) => {
    const rY = tTop + rh + i * rh;
    doc.setFillColor(i % 2 === 0 ? 248 : 255, i % 2 === 0 ? 250 : 255, i % 2 === 0 ? 252 : 255);
    doc.rect(mg, rY, cw, rh, 'F');
    doc.setFont('times', 'normal'); doc.setFontSize(10); doc.setTextColor(30, 30, 30);
    doc.text(row.desc, mg + 4, rY + 6.5);
    if (row.color) { doc.setTextColor(...row.color); doc.setFont('times', 'bold'); }
    doc.text(row.amt, pw - mg - 4, rY + 6.5, { align: 'right' });
    doc.setTextColor(30, 30, 30);
  });
  // Table border
  doc.setDrawColor(210, 215, 220); doc.setLineWidth(0.3);
  doc.roundedRect(mg, tTop, cw, rh * 4, 1.5, 1.5, 'S');
  y = tTop + rh * 4 + 8;

  // ── Status Badge ──
  const [br, bg, bb] = amountLeft <= 0 ? [220, 252, 231] : (Number(d.amount_paid) > 0 ? [254, 243, 199] : [254, 226, 226]);
  const [tr2, tg2, tb2] = amountLeft <= 0 ? [21, 128, 61] : (Number(d.amount_paid) > 0 ? [146, 64, 14] : [153, 27, 27]);
  doc.setFillColor(br, bg, bb);
  doc.roundedRect(mg, y, 62, 9, 2, 2, 'F');
  doc.setFont('times', 'bold'); doc.setFontSize(9); doc.setTextColor(tr2, tg2, tb2);
  doc.text('STATUS: ' + status, mg + 4, y + 6);
  doc.setTextColor(0, 0, 0);
  y += 16;

  // ── Signature ──
  doc.setFontSize(9.5); doc.setFont('times', 'bold'); doc.setTextColor(60, 60, 60);
  doc.text('Authorized Signature:', pw - mg - 68, y); y += 4;
  try {
    const sig = await loadImg('receipts/static/signature.png');
    doc.addImage(sig, 'PNG', pw - mg - 68, y, 55, 18); y += 22;
  } catch (e) { y += 14; }
  doc.setFont('times', 'normal'); doc.setFontSize(10); doc.setTextColor(20, 20, 20);
  doc.text('Eyram Dela Kuwornu', pw - mg - 68, y);
  doc.setFontSize(8.5); doc.setTextColor(100, 100, 100);
  doc.text('Director — Forgedcore Engineering Ltd', pw - mg - 68, y + 5);

  // ── Footer Note ──
  doc.setFont('times', 'italic'); doc.setFontSize(8); doc.setTextColor(140, 140, 140);
  doc.text('This document is computer generated and valid without stamp.', pw / 2, safeBottom - 2, { align: 'center' });

  doc.save('payslip_' + d.payslip_no.replace(/\//g, '_') + '.pdf');
}

function loadImg(url) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('Image not found'));
    img.src = url;
  });
}
