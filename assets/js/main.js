// Main JavaScript file

// Toggle sidebar on mobile
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
}

// Load sections based on department selection
function loadSections(departmentId, targetSelectId) {
    if (!departmentId) return;
    
    fetch(`../api/get_sections.php?department_id=${departmentId}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById(targetSelectId);
            select.innerHTML = '<option value="">Select Section</option>';
            data.forEach(section => {
                select.innerHTML += `<option value="${section.id}">${section.name}</option>`;
            });
        });
}

// Generate property number
function generatePropertyNo(category, sectionId, targetInputId) {
    if (!category || !sectionId) return;
    
    fetch(`../api/get_next_property_no.php?category=${category}&section_id=${sectionId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById(targetInputId).value = data.property_no;
        });
}

// Print barcode
function printBarcode(barcodeData) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Barcode</title>
            <style>
                body { display: flex; justify-content: center; align-items: center; height: 100vh; }
                .barcode { text-align: center; }
                img { max-width: 300px; }
            </style>
        </head>
        <body>
            <div class="barcode">
                <img src="${barcodeData}" onload="window.print(); window.close();">
            </div>
        </body>
        </html>
    `);
}

// Export table to CSV
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = [];
        cells.forEach(cell => {
            rowData.push('"' + cell.innerText.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.csv';
    a.click();
}