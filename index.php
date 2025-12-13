<?php
/**
 * Main form page for ForgedCore Receipt Generator
 */
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>ForgedCore Receipt Generator</title>
  <!-- jsPDF library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    body { 
      font-family: Arial, sans-serif; 
      background: #f2f2f2; 
      padding: 30px; 
      margin: 0;
    }
    .container {
      max-width: 500px;
      margin: 0 auto;
    }
    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 30px;
    }
    form { 
      background: white; 
      padding: 30px; 
      border-radius: 8px; 
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    label { 
      display: block; 
      margin: 15px 0 5px 0; 
      font-weight: bold;
      color: #555;
    }
    input[type="text"], 
    input[type="number"], 
    textarea { 
      width: 100%; 
      padding: 10px; 
      margin: 5px 0 15px 0; 
      border: 1px solid #ddd;
      border-radius: 4px;
      box-sizing: border-box;
      font-size: 14px;
    }
    textarea {
      resize: vertical;
      min-height: 80px;
    }
    button { 
      width: 100%; 
      padding: 12px; 
      background: #007bff; 
      color: white; 
      border: none; 
      border-radius: 5px; 
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      margin-top: 10px;
    }
    button:hover {
      background: #0056b3;
    }
    a { 
      text-align: center; 
      display: block; 
      margin-top: 20px; 
      color: #444;
      text-decoration: none;
    }
    a:hover {
      color: #007bff;
      text-decoration: underline;
    }
    .error {
      color: red;
      margin-top: 10px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>ForgedCore Engineering - Receipt Generator</h2>
    <form id="receiptForm">
      <label for="name">Client Name:</label>
      <input type="text" id="name" name="name" required>

      <label for="address">Address:</label>
      <input type="text" id="address" name="address" required>

      <label for="contact">Contact:</label>
      <input type="text" id="contact" name="contact" required>

      <label for="description">Description:</label>
      <textarea id="description" name="description" required></textarea>

      <label for="total">Total Payment (GHS):</label>
      <input type="number" id="total" name="total" step="0.01" min="0" required>

      <label for="paid">Amount Paid (GHS):</label>
      <input type="number" id="paid" name="paid" step="0.01" min="0" required>

      <button type="submit">Generate PDF Receipt</button>
    </form>
    <div id="errorMsg" class="error" style="display: none;"></div>
    <a href="clients.php">View All Receipts</a>
  </div>

  <script>
    const { jsPDF } = window.jspdf;

    document.getElementById('receiptForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const formData = {
        name: document.getElementById('name').value.trim(),
        address: document.getElementById('address').value.trim(),
        contact: document.getElementById('contact').value.trim(),
        description: document.getElementById('description').value.trim(),
        total: parseFloat(document.getElementById('total').value) || 0,
        paid: parseFloat(document.getElementById('paid').value) || 0
      };

      // Validate
      if (!formData.name) {
        showError('Client name is required');
        return;
      }

      try {
        // Get invoice number and date from server
        const response = await fetch('get-invoice-info.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ name: formData.name })
        });

        const invoiceData = await response.json();
        if (!response.ok) {
          throw new Error(invoiceData.error || 'Failed to get invoice information');
        }

        // Generate PDF using jsPDF
        await generatePDF({ ...formData, ...invoiceData });

        // Save to database
        const saveResponse = await fetch('save-receipt.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ ...formData, ...invoiceData })
        });

        const saveResult = await saveResponse.json();
        if (!saveResponse.ok) {
          console.error('Failed to save to database:', saveResult.error);
          // PDF was generated, so we continue anyway
        }

        // Reset form
        document.getElementById('receiptForm').reset();
        hideError();

      } catch (error) {
        showError('Error: ' + error.message);
        console.error(error);
      }
    });

    async function generatePDF(data) {
      const doc = new jsPDF();
      const pageWidth = doc.internal.pageSize.getWidth();
      const margin = 30;
      const contentWidth = pageWidth - (margin * 2);
      let yPos = margin;

      // Set font to Times (serif)
      doc.setFont('times');

      // Try to load logo
      let logoLoaded = false;
      try {
        const logoImg = await loadImage('receipts/static/logo.png');
        doc.addImage(logoImg, 'PNG', margin, yPos, 30, 30);
        logoLoaded = true;
      } catch(e) {
        // Logo not found or can't load, continue without it
        console.log('Logo not available');
      }

      // Company info (right aligned)
      doc.setFontSize(11);
      doc.setFont('times', 'bold');
      doc.text('FORGEDCORE ENGINEERING LTD', pageWidth - margin, yPos + 10, { align: 'right' });
      
      doc.setFontSize(9);
      doc.setFont('times', 'normal');
      doc.text('Kpobiman (Amasaman), Accra', pageWidth - margin, yPos + 16, { align: 'right' });
      doc.text('0540202096 / 0545286665 | forgedcoreengineering@gmail.com', pageWidth - margin, yPos + 22, { align: 'right' });
      doc.text('www.forgedcoreengineering.com', pageWidth - margin, yPos + 28, { align: 'right' });

      yPos = logoLoaded ? yPos + 40 : yPos + 20;

      // Title
      doc.setFontSize(18);
      doc.setFont('times', 'bold');
      doc.text('RECEIPT', pageWidth / 2, yPos, { align: 'center' });
      yPos += 15;

      // Invoice and Date (left)
      doc.setFontSize(10);
      doc.setFont('times', 'normal');
      doc.setFont('times', 'bold');
      doc.text('Invoice No:', margin, yPos);
      doc.setFont('times', 'normal');
      doc.text(data.invoice_no, margin + 35, yPos);
      doc.setFont('times', 'bold');
      doc.text('Date:', margin, yPos + 7);
      doc.setFont('times', 'normal');
      doc.text(data.date, margin + 35, yPos + 7);

      // Bill To (right)
      doc.setFont('times', 'bold');
      doc.text('BILL TO', pageWidth - margin, yPos, { align: 'right' });
      doc.setFont('times', 'normal');
      doc.text('Client Name: ' + data.name, pageWidth - margin, yPos + 7, { align: 'right' });
      doc.text('Address: ' + data.address, pageWidth - margin, yPos + 14, { align: 'right' });
      doc.text('Contact: ' + data.contact, pageWidth - margin, yPos + 21, { align: 'right' });

      yPos += 35;

      // Description
      doc.setFontSize(12);
      doc.setFont('times', 'bold');
      doc.text(data.description.toUpperCase(), pageWidth / 2, yPos, { align: 'center' });
      yPos += 15;

      // Payment Summary
      doc.setFontSize(11);
      doc.setFont('times', 'bold');
      doc.text('PAYMENT SUMMARY', pageWidth / 2, yPos, { align: 'center' });
      yPos += 12;

      // Payment table
      const tableTop = yPos;
      const col1Width = contentWidth * 0.72;
      const col2Width = contentWidth * 0.28;
      const rowHeight = 10;

      // Table header
      doc.setFillColor(245, 245, 245);
      doc.rect(margin, tableTop, contentWidth, rowHeight, 'F');
      doc.setFont('times', 'bold');
      doc.text('DESCRIPTION', margin + 5, tableTop + 7);
      doc.text('AMOUNT (GHS)', pageWidth - margin - 5, tableTop + 7, { align: 'right' });

      // Table rows
      const balance = data.total - data.paid;
      const rows = [
        { desc: 'TOTAL SUM', amount: data.total.toFixed(2), color: null },
        { desc: 'TOTAL AMOUNT PAID', amount: data.paid.toFixed(2), color: [0, 128, 0] },
        { desc: 'OUTSTANDING BALANCE', amount: balance.toFixed(2), color: [255, 0, 0] }
      ];

      rows.forEach((row, index) => {
        const rowY = tableTop + rowHeight + (index * rowHeight);
        if (index % 2 === 0) {
          doc.setFillColor(247, 247, 247);
          doc.rect(margin, rowY, contentWidth, rowHeight, 'F');
        }
        doc.setFont('times', 'normal');
        doc.text(row.desc, margin + 5, rowY + 7);
        if (row.color) {
          doc.setTextColor(row.color[0], row.color[1], row.color[2]);
          doc.setFont('times', 'bold');
        }
        doc.text(row.amount, pageWidth - margin - 5, rowY + 7, { align: 'right' });
        doc.setTextColor(0, 0, 0);
        doc.setFont('times', 'normal');
      });

      yPos = tableTop + rowHeight * 4 + 30;

      // Signature section
      const sigX = margin + (contentWidth * 0.64);
      doc.setFont('times', 'bold');
      doc.text('Authorized Signature:', sigX, yPos);
      yPos += 15;

      // Try to add signature image
      try {
        const sigImg = await loadImage('receipts/static/signature.png');
        doc.addImage(sigImg, 'PNG', sigX, yPos, 60, 20);
        yPos += 25;
      } catch(e) {
        yPos += 10;
      }

      doc.setFont('times', 'normal');
      doc.text('Eyram Dela Kuwornu', sigX, yPos);
      doc.setFontSize(9);
      doc.text('(Director – Forgedcore Engineering Ltd)', sigX, yPos + 7);

      // Save PDF
      const pdfName = 'receipt_' + data.invoice_no.replace(/\//g, '_') + '.pdf';
      doc.save(pdfName);
    }

    // Helper function to load images
    function loadImage(url) {
      return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error('Image not found'));
        img.src = url;
      });
    }

    function showError(message) {
      const errorDiv = document.getElementById('errorMsg');
      errorDiv.textContent = message;
      errorDiv.style.display = 'block';
    }

    function hideError() {
      document.getElementById('errorMsg').style.display = 'none';
    }
  </script>
</body>
</html>

