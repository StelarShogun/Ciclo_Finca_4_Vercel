import { cf4Warning } from '../shared/swal.js';

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

    // Validación de rango de fechas personalizadas
    const form = document.getElementById('report-filter-form');
    if (form) {
        form.addEventListener('submit', async function (e) {
            const range = document.getElementById('date-range');
            if (!range || range.value !== 'custom') return;

            const fromInput = document.getElementById('date-from');
            const toInput   = document.getElementById('date-to');

            if (!fromInput || !toInput) return;

            const from = new Date(fromInput.value);
            const to   = new Date(toInput.value);

            if (!fromInput.value || !toInput.value) {
                e.preventDefault();
                await cf4Warning('Debe ingresar ambas fechas para el rango personalizado.', 'Rango incompleto');
                return;
            }

            if (to < from) {
                e.preventDefault();
                await cf4Warning('La fecha de fin debe ser igual o posterior a la fecha de inicio.', 'Rango de fechas inválido');
                toInput.focus();
            }
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