document.addEventListener('DOMContentLoaded', function () {

    // Toggle fechas personalizadas
    function toggleCustomDates(value) {
        const show = value === 'custom';
        document.getElementById('custom-from').style.display = show ? '' : 'none';
        document.getElementById('custom-to').style.display   = show ? '' : 'none';
    }

    const dateRange = document.getElementById('date-range');
    if (dateRange) {
        dateRange.addEventListener('change', function () {
            toggleCustomDates(this.value);
        });
    }

    // Gráfico
    const canvas = document.getElementById('category-chart');
    if (!canvas) return;

    const chartData = JSON.parse(canvas.dataset.chart);

    new Chart(canvas, {
        type: 'pie',
        data: {
            labels: chartData.map(function(r) { return r.label; }),
            datasets: [{
                data: chartData.map(function(r) { return r.value; }),
                backgroundColor: [
                    '#4CAF50','#2196F3','#FF9800',
                    '#9C27B0','#F44336','#00BCD4',
                    '#795548','#607D8B','#E91E63','#009688',
                ],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const d = chartData[context.dataIndex];
                            return ' ₡' + Number(d.value).toLocaleString('es-CR') + ' (' + d.percent + '%)';
                        }
                    }
                }
            }
        }
    });
});