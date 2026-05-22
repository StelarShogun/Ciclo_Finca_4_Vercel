let chartJsPromise = null;

async function loadChartJs() {
    if (window.Chart) {
        return window.Chart;
    }
    if (!chartJsPromise) {
        chartJsPromise = import('chart.js/auto').then((mod) => {
            const Chart = mod.default ?? mod.Chart;
            window.Chart = Chart;
            return Chart;
        });
    }
    return chartJsPromise;
}

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

    /** Etiquetas del eje Y más compactas cuando los montos son muy grandes */
    formatSalesYTick(value) {
        const v = Number(value);
        if (!Number.isFinite(v)) {
            return '';
        }
        const abs = Math.abs(v);
        if (abs >= 1_000_000_000) {
            return '₡' + (v / 1_000_000_000).toLocaleString('es-ES', { maximumFractionDigits: 1 }) + ' MM';
        }
        if (abs >= 1_000_000) {
            return '₡' + (v / 1_000_000).toLocaleString('es-ES', { maximumFractionDigits: 1 }) + ' M';
        }
        if (abs >= 10_000) {
            return '₡' + (v / 1_000).toLocaleString('es-ES', { maximumFractionDigits: 1 }) + ' k';
        }
        return '₡' + Math.round(v).toLocaleString('es-ES');
    }

    /**
     * Ajusta tensión, marcas del eje X y rango del eje Y para que la línea se lea bien
     * con pocos o muchos días y con ventas muy parecidas entre sí.
     */
    syncSalesChartPresentation(chart) {
        if (!chart?.options?.scales || !chart.data?.datasets?.[0]) {
            return;
        }

        const labels = chart.data.labels || [];
        const values = chart.data.datasets[0].data.map(v => Number(v) || 0);
        const n = labels.length;
        const ds = chart.data.datasets[0];
        const y = chart.options.scales.y;
        const x = chart.options.scales.x;

        delete y.max;
        delete y.min;
        delete y.suggestedMax;
        delete y.suggestedMin;

        ds.tension = n > 48 ? 0.1 : n > 22 ? 0.22 : 0.34;
        ds.borderWidth = n > 40 ? 2.5 : 3;

        const maxV = values.length ? Math.max(...values) : 0;
        const minV = values.length ? Math.min(...values) : 0;
        const range = maxV - minV;

        if (!values.length || maxV <= 0) {
            y.beginAtZero = true;
            y.grace = '8%';
        } else if (range === 0 && maxV > 0) {
            y.beginAtZero = false;
            y.min = Math.max(0, maxV * 0.88);
            y.max = maxV * 1.12;
            y.grace = '0%';
        } else if (minV >= 0 && range / maxV < 0.22) {
            y.beginAtZero = false;
            const pad = Math.max(range * 0.55, maxV * 0.04);
            y.min = Math.max(0, minV - pad);
            y.suggestedMax = maxV + pad;
            y.grace = '6%';
        } else {
            y.beginAtZero = true;
            y.grace = '10%';
        }

        x.offset = true;
        x.ticks.maxRotation = n > 12 ? 48 : 0;
        x.ticks.minRotation = n > 18 ? 28 : 0;
        x.ticks.autoSkipPadding = n > 25 ? 10 : 5;
        x.ticks.maxTicksLimit = Math.min(22, Math.max(6, n));
    }

    buildSalesChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: { left: 0, right: 8, top: 8, bottom: 4 }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#235347',
                    borderWidth: 1,
                    callbacks: {
                        label: (context) => {
                            return 'Ventas: ₡' + context.parsed.y.toLocaleString('es-ES');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: {
                        color: '#6c757d',
                        font: { size: 11, weight: '500' },
                        maxRotation: 0,
                        minRotation: 0,
                        autoSkip: true,
                        autoSkipPadding: 6,
                        maxTicksLimit: 14
                    }
                },
                y: {
                    beginAtZero: true,
                    grace: '10%',
                    grid: { color: '#f1f3f4', drawBorder: false },
                    ticks: {
                        color: '#6c757d',
                        font: { size: 11, weight: '500' },
                        maxTicksLimit: 9,
                        callback: (value) => this.formatSalesYTick(value)
                    }
                }
            },
            interaction: { intersect: false, mode: 'index' }
        };
    }

    init() {
        this.updateCurrentTime();
        this.bindEvents();

        // Chart.js + the two dashboard charts are deferred until the page has
        // finished loading. After `window.load` the LCP is already captured, so
        // pulling in ~70 kB gzipped of charting code can no longer regress it.
        const scheduleCharts = () => {
            const runCharts = () => {
                const chartHosts = document.querySelectorAll('#sales-chart, #category-chart');
                if (!chartHosts.length) return;
                void this.initCharts();
            };
            if (typeof requestIdleCallback === 'function') {
                requestIdleCallback(runCharts, { timeout: 2500 });
            } else {
                setTimeout(runCharts, 500);
            }
        };
        if (document.readyState === 'complete') {
            scheduleCharts();
        } else {
            window.addEventListener('load', scheduleCharts, { once: true });
        }

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
            const formattedDate = now.toLocaleDateString('es-ES', options);
            timeElement.textContent = formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);
        }
    }

    async initCharts() {
        await loadChartJs();
        const initialData = await this.loadChartDataset('7d').catch(() => null);
        this.initSalesChart(initialData);
        this.initCategoryChart(initialData);
    }

    /**
     * Single source of truth for /dashboard/chart-data. Memoises responses per
     * period so the sales and category charts share one network call instead of
     * each module firing their own request.
     */
    async loadChartDataset(period = '7d') {
        this._chartDatasetCache ??= new Map();
        const cached = this._chartDatasetCache.get(period);
        if (cached) return cached;

        const promise = (async () => {
            const url = '/dashboard/chart-data' + (period ? `?period=${period}` : '');
            const csrf = document.querySelector('meta[name="csrf-token"]');
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
                },
            });
            if (!response.ok) throw new Error('Error al cargar datos del dashboard');
            const data = await response.json();
            if (!data || data.success !== true) {
                throw new Error(data?.message || 'Datos del dashboard no válidos');
            }
            return data;
        })();

        this._chartDatasetCache.set(period, promise);
        try {
            return await promise;
        } catch (err) {
            this._chartDatasetCache.delete(period);
            throw err;
        }
    }

    initSalesChart(preloaded) {
        const ctx = document.getElementById('sales-chart');
        if (!ctx || !window.Chart) return;

        this.loadSalesData(preloaded).then(salesData => {
            this.salesChart = new Chart(ctx, {
                type: 'line',
                data: salesData,
                options: this.buildSalesChartOptions()
            });
            this.syncSalesChartPresentation(this.salesChart);
            this.salesChart.update();
        }).catch(error => {
            console.error('Error al cargar datos de ventas:', error);
            // Show fallback empty chart on error
            const emptySales = {
                labels: ['Sin datos'],
                datasets: [{
                    label: 'Ventas (₡)',
                    data: [0],
                    borderColor: '#e0e0e0',
                    backgroundColor: 'rgba(224, 224, 224, 0.12)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.25,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            };
            this.salesChart = new Chart(ctx, {
                type: 'line',
                data: emptySales,
                options: this.buildSalesChartOptions()
            });
            this.syncSalesChartPresentation(this.salesChart);
            this.salesChart.update();
        });
    }

    /** Sincroniza el aspecto de la leyenda HTML con segmentos ocultos del donut (misma idea que la leyenda nativa). */
    syncCategoryLegendHiddenStyles() {
        const el = document.getElementById('category-chart-legend');
        const chart = this.categoryChart;
        if (!el || !chart) return;

        const meta = chart.getDatasetMeta(0);
        el.querySelectorAll('.category-chart-legend-item[data-legend-index]').forEach(row => {
            const i = Number(row.dataset.legendIndex);
            const hidden = typeof chart.getDataVisibility === 'function'
                ? !chart.getDataVisibility(i)
                : !!(meta.data[i] && meta.data[i].hidden);
            row.classList.toggle('category-chart-legend-item--hidden', hidden);
        });
    }

    /** Clic en nombre/categoría: oculta o muestra ese trozo del donut (API Chart.js). */
    toggleCategoryLegendSegment(index) {
        const chart = this.categoryChart;
        if (!chart) return;

        if (typeof chart.toggleDataVisibility === 'function') {
            chart.toggleDataVisibility(index);
            chart.update();
        } else {
            const meta = chart.getDatasetMeta(0);
            if (meta?.data[index]) {
                meta.data[index].hidden = !meta.data[index].hidden;
            }
            chart.update();
        }

        this.syncCategoryLegendHiddenStyles();
    }

    /** Leyenda HTML con scroll aparte del canvas (Chart.js dibuja la leyenda en el mismo canvas). */
    renderCategoryHtmlLegend(chartData) {
        const el = document.getElementById('category-chart-legend');
        if (!el) return;

        const labels = chartData.labels || [];
        const dataset = chartData.datasets?.[0];
        const values = dataset?.data || [];
        const colorsRaw = dataset?.backgroundColor;
        const colors = Array.isArray(colorsRaw)
            ? colorsRaw
            : (colorsRaw ? [colorsRaw] : []);

        el.replaceChildren();

        if (!labels.length) {
            const empty = document.createElement('p');
            empty.className = 'category-chart-legend-empty';
            empty.style.margin = '0';
            empty.style.fontSize = '0.85rem';
            empty.style.color = 'var(--color-muted)';
            empty.textContent = 'Sin categorías';
            el.appendChild(empty);
            return;
        }

        labels.forEach((label, i) => {
            const row = document.createElement('div');
            row.className = 'category-chart-legend-item';
            row.setAttribute('role', 'listitem');
            row.dataset.legendIndex = String(i);
            row.tabIndex = 0;
            row.setAttribute('aria-label', `Mostrar u ocultar categoría: ${label != null ? String(label) : ''}`);

            const swatch = document.createElement('span');
            swatch.className = 'category-chart-legend-swatch';
            swatch.setAttribute('aria-hidden', 'true');
            swatch.style.background = colors[i % Math.max(colors.length, 1)] || '#cccccc';

            const name = document.createElement('span');
            name.className = 'category-chart-legend-name';
            name.textContent = label != null ? String(label) : '';

            const val = document.createElement('span');
            val.className = 'category-chart-legend-value';
            val.textContent = String(values[i] ?? 0);

            row.appendChild(swatch);
            row.appendChild(name);
            row.appendChild(val);

            row.addEventListener('click', () => this.toggleCategoryLegendSegment(i));
            row.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggleCategoryLegendSegment(i);
                }
            });

            el.appendChild(row);
        });

        this.syncCategoryLegendHiddenStyles();
    }

    initCategoryChart(preloaded) {
        const ctx = document.getElementById('category-chart');
        if (!ctx) return;

        this.loadCategoryData(preloaded).then(categoryData => {
            // Si antes se usó barras horizontales, el wrapper pudo quedar con altura inline enorme.
            if (ctx.parentNode) {
                ctx.parentNode.style.height = '';
            }

            this.categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: categoryData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#235347',
                            borderWidth: 1,
                            callbacks: {
                                label: function (context) {
                                    const chart = context.chart;
                                    const ds = context.dataset;
                                    const meta = chart.getDatasetMeta(context.datasetIndex);
                                    let visibleTotal = 0;
                                    meta.data.forEach((arc, j) => {
                                        if (!arc.hidden) {
                                            visibleTotal += Number(ds.data[j]) || 0;
                                        }
                                    });
                                    const value = typeof context.parsed === 'number'
                                        ? context.parsed
                                        : (context.raw ?? 0);
                                    const percentage = visibleTotal > 0
                                        ? ((value / visibleTotal) * 100).toFixed(1)
                                        : '0.0';
                                    return `${context.label}: ${value} productos (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '58%'
                }
            });
            this.renderCategoryHtmlLegend(categoryData);
        }).catch(error => {
            console.error('Error al cargar datos de categorías:', error);
            // Show fallback empty chart on error
            const fallbackData = {
                labels: ['Sin datos'],
                datasets: [{
                    label: 'Productos',
                    data: [1],
                    backgroundColor: ['#e0e0e0'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            };
            this.categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: fallbackData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '60%'
                }
            });
            this.renderCategoryHtmlLegend(fallbackData);
        });
    }

    async loadSalesData(preloaded) {
        try {
            const data = preloaded ?? await this.loadChartDataset('7d');

            if (data.success && data.sales) {
                const labels = this.formatSalesChartLabels(data.sales);
                const values = data.sales.map(sale => parseFloat(sale.total) || 0);

                return {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas (₡)',
                        data: values,
                        borderColor: '#235347',
                        backgroundColor: 'rgba(46, 125, 50, 0.12)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.34,
                        pointBackgroundColor: '#235347',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: (ctx) => {
                            const len = ctx.chart.data.labels.length;
                            if (len <= 8) return 5;
                            if (len <= 22) return 4;
                            if (len <= 55) return 2;
                            return 0;
                        },
                        pointHoverRadius: (ctx) => {
                            const len = ctx.chart.data.labels.length;
                            return len <= 22 ? 7 : 4;
                        }
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

    async loadCategoryData(preloaded) {
        try {
            const data = preloaded ?? await this.loadChartDataset('7d');

            if (data.success && data.categories) {
                // Ordenamos las categorías por total descendente pero mostramos todas,
                // el scroll del contenedor se encarga de manejar cantidades grandes.
                const sorted = [...data.categories].sort((a, b) => (b.total || 0) - (a.total || 0));

                const labels = sorted.map(cat => {
                    const name = cat.categoria ?? cat.name ?? cat.category;
                    return name != null && String(name).trim() !== '' ? String(name) : 'Sin categoría';
                });
                const values = sorted.map(cat => cat.total || 0);

                // Generate dynamic colors for each category (incluyendo "Otras categorías" si existe)
                const colors = this.generateColors(values.length);

                return {
                    labels,
                    datasets: [{
                        label: 'Productos por categoría',
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
            '#235347', '#8EB69B', '#163832', '#DAF1DE', '#1976d2',
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

        // Period selection buttons for charts
        const chartBtns = document.querySelectorAll('.chart-btn');
        chartBtns.forEach(btn => {
            btn.addEventListener('click', (e) => this.changeChartPeriod(e.target));
        });

        this.animateElements();
    }

    async refreshDashboard() {
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            icon.classList.add('fa-spin');
            refreshBtn.disabled = true;
        }

        try {
            this._chartDatasetCache?.clear();
            await this.loadDashboardData();
            if (this.salesChart) {
                const activeBtn = document.querySelector('.chart-btn.active');
                const period = activeBtn?.dataset.period || '7d';
                await this.updateSalesChart(period);
            }
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

    async updateSalesChart(period) {
        if (!this.salesChart) return;

        try {
            const data = await this.loadChartDataset(period);

            if (data.success && data.sales) {
                const labels = this.formatSalesChartLabels(data.sales);
                const values = data.sales.map(sale => parseFloat(sale.total) || 0);

                this.salesChart.data.labels = labels;
                this.salesChart.data.datasets[0].data = values;
                this.syncSalesChartPresentation(this.salesChart);
                this.salesChart.update('active');
            }
        } catch (error) {
            console.error('Error al actualizar gráfico de ventas:', error);
            this.showNotification('Error al cargar datos de ventas', 'error');
        }
    }

    /**
     * Fetch KPI numbers from /dashboard/data. The Blade template already prints
     * the cached server-side values, so this only runs when the user clicks the
     * manual "Actualizar" button.
     */
    async loadDashboardData() {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]');
            const response = await fetch('/dashboard/data', {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
                },
            });

            if (!response.ok) throw new Error('Error al cargar datos del dashboard');

            const data = await response.json();

            if (data.success) {
                this.updateKPIs(data);
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
            const sales = parseFloat(data.todaySales) || 0;
            todaySalesEl.textContent = '₡' + Math.round(sales).toLocaleString('es-CR', {
                maximumFractionDigits: 0
            });
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

            // Clean the value to extract only numbers, handling currency and formatting
            const cleaned = finalValue
                .replace(/[^\d.,]/g, '')
                .replace(/\./g, '')
                .replace(',', '.');

            const numericValue = parseFloat(cleaned) || 0;

            if (numericValue > 0) {
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
            const formatted = isCurrency
                ? '₡' + Math.round(current).toLocaleString('es-ES', { maximumFractionDigits: 0 })
                : current.toLocaleString('es-ES', { maximumFractionDigits: 0 });

            element.textContent = formatted;

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    /**
     * Hook left intentionally as a no-op: the previous implementation set
     * `opacity: 0` and `transform: translateY(20px)` on every card before an
     * IntersectionObserver restored them, which shifted layout right after
     * paint and hurt CLS. The cards now stay in their natural position.
     */
    animateElements() {
        /* no-op */
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
            background: ${type === 'success' ? '#235347' : type === 'error' ? '#d32f2f' : '#1976d2'};
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