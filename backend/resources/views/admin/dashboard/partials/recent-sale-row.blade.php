<tr>
    <td class="dashboard-table__col-invoice">
        <span class="dashboard-table__cell-truncate" title="{{ $sale->adminDashboardInvoiceLabel() }}">
            {{ $sale->adminDashboardInvoiceLabel() }}
        </span>
    </td>
    <td class="dashboard-table__col-client">
        <span class="dashboard-table__cell-truncate" title="{{ $sale->adminDashboardClientLabel() }}">
            {{ $sale->adminDashboardClientLabel() }}
        </span>
    </td>
    <td class="dashboard-table__col-total">₡{{ number_format($sale->total, 0, ',', '.') }}</td>
    <td class="dashboard-table__col-date" title="{{ $sale->adminSaleDateLabel() }}">
        {{ $sale->adminSaleDateShortLabel() }}
    </td>
    <td class="dashboard-table__col-status">
        <span class="status-badge {{ $sale->adminDashboardStatusBadgeClass() }}"
            title="{{ $sale->adminDashboardStatusTitle() }}">
            {{ $sale->adminDashboardStatusShortLabel() }}
        </span>
    </td>
</tr>
