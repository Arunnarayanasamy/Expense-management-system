<?php 
    include_once '../init.php'; 

    // User login check
    if ($getFromU->loggedIn() === false) {
        header('Location: ../index.php');
    }

    include_once 'skeleton.php';

    // Get expense data
    $dtexp = $getFromE->dtwise($_SESSION['UserId'], $_POST['dtfrom'], $_POST['dtto']);
    $labels = [];
    $values = [];

    if ($dtexp !== NULL) {
        foreach ($dtexp as $row) {
            $labels[] = date("d-m-Y", strtotime($row->Date));
            $values[] = (float)$row->Cost;
        }
    }
?>

<div class="wrapper">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="font-family:'Arial Black', Gadget, sans-serif; font-size: 1.3em; text-align: center; margin: 0;">
                        Expenses incurred between <?php echo date("d-m-Y",strtotime($_POST['dtfrom'])) ?> and <?php echo date("d-m-Y",strtotime($_POST['dtto'])) ?> :
                    </h4>    
                    <div>
                        <button onclick="printTable()" style="background:none; border:none; cursor:pointer; font-weight:bold; color:#000;">
                            🖨️ <span style="font-family: 'Segoe UI';">Print</span>
                        </button>
                        &nbsp;&nbsp;
                        <button onclick="downloadPDF()" style="background:none; border:none; cursor:pointer; font-weight:bold; color:#000;">
                            📄 <span style="font-family: 'Segoe UI';">PDF</span>
                        </button>
                    </div>
                </div>
                <div class="card-content" id="print-section">
                    <table id="expense-table">
                        <thead>
                            <tr>
                                <th style="font-family: 'Arial Black', Gadget, sans-serif; font-weight: bold;">#</th>
                                <th style="font-family: 'Arial Black', Gadget, sans-serif; font-weight: bold;">Description</th>
                                <th style="font-family: 'Arial Black', Gadget, sans-serif; font-weight: bold;">Cost</th>
                                <th style="font-family: 'Arial Black', Gadget, sans-serif; font-weight: bold;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if($dtexp !== NULL) {
                                    $x = 1;
                                    foreach ($dtexp as $row) {
                                        echo "<tr>
                                            <td>".$x++."</td>
                                            <td>".$row->Item."</td>
                                            <td>₹ ".$row->Cost."</td>
                                            <td>".date("d-m-Y", strtotime($row->Date))."</td>
                                        </tr>";    
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Graph Section -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3>Expense Graph</h3>
                </div>
                <div class="card-content">
                    <canvas id="myChart" width="400" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Render the graph -->
<script>
    const ctx = document.getElementById('myChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Expenses (Rs.)',
                data: <?php echo json_encode($values); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: '#ff3d67',
                borderWidth: 2,
                tension: 0.3,
                pointBackgroundColor: '#ff3d67',
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<!-- jsPDF & AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<!-- Print & PDF Buttons -->
<script>
    function printTable() {
        const printContents = document.getElementById('print-section').innerHTML;
        const originalContents = document.body.innerHTML;
        const heading = `
            <h2 style="text-align:center; font-family:'Times New Roman', Times, serif; font-size:18px;">
                Expenses Incurred Between <?php echo date("d-m-Y",strtotime($_POST['dtfrom'])) ?> and <?php echo date("d-m-Y",strtotime($_POST['dtto'])) ?>
            </h2><br>`;
        document.body.innerHTML = heading + printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }

    async function downloadPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        doc.setFont("times", "bold");
        doc.setFontSize(14);

        const titleText = "Expenses Incurred Between <?php echo date('d-m-Y', strtotime($_POST['dtfrom'])) ?> and <?php echo date('d-m-Y', strtotime($_POST['dtto'])) ?>";
        doc.text(titleText, (doc.internal.pageSize.getWidth() - doc.getTextWidth(titleText)) / 2, 15);

        doc.autoTable({
            html: '#expense-table',
            startY: 25,
            headStyles: { fillColor: [0, 0, 0] },
            styles: { fontSize: 10 },
            theme: 'grid',
            didParseCell: function (data) {
                if (typeof data.cell.text[0] === 'string' && data.cell.text[0].includes("₹")) {
                    data.cell.text[0] = data.cell.text[0].replace("₹", "Rs.");
                }
            }
        });

        doc.save("Expense_Report.pdf");
    }
</script>
