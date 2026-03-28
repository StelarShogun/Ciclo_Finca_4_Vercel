// Main dashboard controller class
class Dashboard {
    constructor() {
        this.salesChart = null;
        this.categoryChart = null;
        this.init();
    }

    /** Evita desfase de día al parsear "YYYY-MM-DD" como UTC en el navegador */
    parseChartDate(ymd) {
        const s = String(ymd).slice(0, 10);
        const [y, m, d] = s.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    formatSalesChartLabels(sales) {
        const n = sales.length;
        return sales.map(sale => {
            const date = this.parseChartDate(sale.date);
            if (n <= 7) {
                return date.toLocaleDateString('es-ES', { weekday: 'short' });
            }
            if (n <= 16) {
                return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
            }
            return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'numeric' });
        });
    }

    // Initialize dashboard components
    init() {
        this.updateCurrentTime();
        this.initCharts();
        this.bindEvents();
        this.loadDashboardData();
        
        // Update time every minute
        setInterval(() => this.updateCurrentTime(), 60000);
    }

    // Display current date and time in the header
    updateCurrentTime() {
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = now.toLocaleDateString('es-ES', options);
        }
    }

    // Initialize both sales and category charts
    initCharts() {
        this.initSalesChart();
        this.initCategoryChart();
    }

    // Create line chart for sales data
    initSalesChart() {
        const ctx = document.getElementById('sales-chart');
        if (!ctx) return;

        // Fetch real sales data from server
        this.loadSalesData().then(salesData => {
            this.salesChart = new Chart(ctx, {
                type: 'line',
                data: salesData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#2e7d32',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    return 'Ventas: ₡' + context.parsed.y.toLocaleString('es-ES');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#6c757d',
                                font: { size: 12, weight: '500' }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f3f4', drawBorder: false },
                            ticks: {
                                color: '#6c757d',
                                font: { size: 12, weight: '500' },
                                callback: function(value) {
                                    return '₡' + value.toLocaleString('es-ES');
                                }
                            }
                        }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
        }).catch(error => {
            console.error('Error al cargar datos de ventas:', error);
            // Show fallback empty chart on error
            this.salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Sin datos'],
                    datasets: [{
                        label: 'Ventas (₡)',
                        data: [0],
                        borderColor: '#e0e0e0',
                        backgroundColor: 'rgba(224, 224, 224, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#6c757d',
                                font: { size: 12, weight: '500' }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f3f4', drawBorder: false },
                            ticks: {
                                color: '#6c757d',
                                font: { size: 12, weight: '500' },
                                callback: function(value) {
                                    return '₡' + value.toLocaleString('es-ES');
                                }
                            }
                        }
                    }
                }
            });
        });
    }

    // Create doughnut chart for category distribution
    initCategoryChart() {
        const ctx = document.getElementById('category-chart');
        if (!ctx) return;

        // Fetch real category data from server
        this.loadCategoryData().then(categoryData => {
            this.categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: categoryData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: { size: 12, weight: '500' },
                                color: '#6c757d'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#2e7d32',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + percentage + '%';
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }).catch(error => {
            console.error('Error al cargar datos de categorías:', error);
            // Show fallback empty chart on error
            this.categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Sin datos'],
                    datasets: [{
                        data: [1],
                        backgroundColor: ['#e0e0e0'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: { size: 12, weight: '500' },
                                color: '#6c757d'
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        });
    }

    // Fetch sales data from backend API
    async loadSalesData() {
        try {
            const response = await fetch('/dashboard/chart-data', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) throw new Error('Error al cargar datos de ventas');

            const data = await response.json();
            
            if (data.success && data.sales) {
                const labels = this.formatSalesChartLabels(data.sales);
                const values = data.sales.map(sale => parseFloat(sale.total) || 0);
                
                return {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas (₡)',
                        data: values,
                        borderColor: '#2e7d32',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#2e7d32',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                };
            } else {
                throw new Error('Datos de ventas no válidos');
            }
        } catch (error) {
            console.error('Error al cargar datos de ventas:', error);
            throw error;
        }
    }

    // Fetch category data from backend API
    async loadCategoryData() {
        try {
            const response = await fetch('/dashboard/chart-data', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) throw new Error('Error al cargar datos de categorías');

            const data = await response.json();
            
            if (data.success && data.categories) {
                // Format data for Chart.js doughnut chart
                const labels = data.categories.map(cat => cat.categoria);
                const values = data.categories.map(cat => cat.total);
                
                // Generate dynamic colors for each category
                const colors = this.generateColors(values.length);
                
                return {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 10
                    }]
                };
            } else {
                throw new Error('Datos de categorías no válidos');
            }
        } catch (error) {
            console.error('Error al cargar datos de categorías:', error);
            throw error;
        }
    }

    // Generate a palette of colors for chart segments
    generateColors(count) {
        const baseColors = [
            '#2e7d32', '#4caf50', '#81c784', '#a5d6a7', '#1976d2',
            '#ff9800', '#9c27b0', '#f44336', '#00bcd4', '#795548'
        ];
        
        const colors = [];
        for (let i = 0; i < count; i++) {
            colors.push(baseColors[i % baseColors.length]);
        }
        return colors;
    }

    // Attach DOM event listeners
    bindEvents() {
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshDashboard());
        }

        const exportBtn = document.getElementById('export-report');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReport());
        }

        // Period selection buttons for charts
        const chartBtns = document.querySelectorAll('.chart-btn');
        chartBtns.forEach(btn => {
            btn.addEventListener('click', (e) => this.changeChartPeriod(e.target));
        });

        this.animateElements();
    }

    // Reload dashboard data manually
    async refreshDashboard() {
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            icon.classList.add('fa-spin');
            refreshBtn.disabled = true;
        }

        try {
            await this.loadDashboardData();
            this.showNotification('Dashboard actualizado correctamente', 'success');
        } catch (error) {
            console.error('Error al actualizar dashboard:', error);
            this.showNotification('Error al actualizar dashboard', 'error');
        } finally {
            if (refreshBtn) {
                const icon = refreshBtn.querySelector('i');
                icon.classList.remove('fa-spin');
                refreshBtn.disabled = false;
            }
        }
    }

    // Export current dashboard as a printable report
    exportReport() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Reporte Dashboard - ${new Date().toLocaleDateString('es-ES')}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .section { margin-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Reporte del Dashboard</h1>
                        <p>Generado el ${new Date().toLocaleDateString('es-ES')} a las ${new Date().toLocaleTimeString('es-ES')}</p>
                    </div>
                    <div class="section">
                        <h2>Resumen de KPIs</h2>
                        <p>Total Productos: ${document.getElementById('total-products')?.textContent || 'N/A'}</p>
                        <p>Ventas Hoy: ${document.getElementById('today-sales')?.textContent || 'N/A'}</p>
                        <p>Proveedores: ${document.getElementById('total-suppliers')?.textContent || 'N/A'}</p>
                        <p>Stock Bajo: ${document.getElementById('low-stock')?.textContent || 'N/A'}</p>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    // Handle chart period change (daily/weekly/monthly)
    changeChartPeriod(button) {
        // Remove active class from all period buttons
        document.querySelectorAll('.chart-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        button.classList.add('active');

        const period = button.dataset.period;
        this.updateSalesChart(period);
    }

    // Refresh sales chart with new period data
    async updateSalesChart(period) {
        if (!this.salesChart) return;

        try {
            const response = await fetch(`/dashboard/chart-data?period=${period}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) throw new Error('Error al cargar datos de ventas');

            const data = await response.json();
            
            if (data.success && data.sales) {
                const labels = this.formatSalesChartLabels(data.sales);
                const values = data.sales.map(sale => parseFloat(sale.total) || 0);
                
                this.salesChart.data.labels = labels;
                this.salesChart.data.datasets[0].data = values;
                this.salesChart.update('active');
            }
        } catch (error) {
            console.error('Error al actualizar gráfico de ventas:', error);
            this.showNotification('Error al cargar datos de ventas', 'error');
        }
    }

    // Fetch main dashboard data (KPIs) from server
    async loadDashboardData() {
        try {
            const response = await fetch('/dashboard/data', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) throw new Error('Error al cargar datos del dashboard');

            const data = await response.json();
            
            console.log('Datos recibidos del servidor:', data);
            
            if (data.success) {
                this.updateKPIs(data);
                this.animateKPIs();
            }
        } catch (error) {
            console.error('Error al cargar datos del dashboard:', error);
            this.showNotification('Error al cargar datos del dashboard', 'error');
        }
    }

    // Update KPI display elements with server data
    updateKPIs(data) {
        const totalProductsEl = document.getElementById('total-products');
        if (totalProductsEl && data.totalProducts !== undefined) {
            totalProductsEl.textContent = data.totalProducts;
        }

        const todaySalesEl = document.getElementById('today-sales');
        if (todaySalesEl && data.todaySales !== undefined) {
            todaySalesEl.textContent = '₡' + data.todaySales.toLocaleString('es-ES');
        }

        const totalSuppliersEl = document.getElementById('total-suppliers');
        if (totalSuppliersEl && data.totalSuppliers !== undefined) {
            totalSuppliersEl.textContent = data.totalSuppliers;
        }

        const lowStockEl = document.getElementById('low-stock');
        if (lowStockEl && data.lowStockProducts !== undefined) {
            console.log('Actualizando stock bajo:', data.lowStockProducts);
            lowStockEl.textContent = data.lowStockProducts;
        }
    }

    // Animate KPI numbers counting up
    animateKPIs() {
        const kpiValues = document.querySelectorAll('.kpi-value');
        kpiValues.forEach(element => {
            const finalValue = element.textContent;
            const numericValue = parseInt(finalValue.replace(/[^\d]/g, ''));
            
            if (!isNaN(numericValue)) {
                this.animateNumber(element, 0, numericValue, 1000);
            }
        });
    }

    // Helper to animate a number increment
    animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        const isCurrency = element.textContent.includes('₡');
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (end - start) * progress);
            const formatted = isCurrency ? 
                '₡' + current.toLocaleString('es-ES') : 
                current.toLocaleString('es-ES');
            
            element.textContent = formatted;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    // Apply fade-in animation on scroll
    animateElements() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        const elements = document.querySelectorAll('.kpi-card, .chart-container, .table-container, .action-card');
        elements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(element);
        });
    }

    // Display temporary toast notification
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#2e7d32' : type === 'error' ? '#d32f2f' : '#1976d2'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: slideInRight 0.3s ease;
        `;

        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(notification);

        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new Dashboard();
});

// Global helper functions for external calls
window.refreshDashboard = () => {
    if (window.dashboardInstance) {
        window.dashboardInstance.refreshDashboard();
    }
};

window.exportReport = () => {
    if (window.dashboardInstance) {
        window.dashboardInstance.exportReport();
    }
};