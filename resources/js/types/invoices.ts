import type { ClientListPagination } from '@/types/pagination';

export type InvoicesTab = 'facturas' | 'canceladas' | 'historial';

export type InvoiceRow = {
  id: number;
  invoiceNumber: string | null;
  saleDateLabel: string;
  statusLabel: string;
  statusTone: 'pending' | 'ready' | 'cancelled' | 'completed' | 'default';
  totalFormatted: string;
  showUrl: string;
};

export type InvoiceListPageProps = {
  tab: InvoicesTab;
  orders: InvoiceRow[];
  pagination: ClientListPagination;
  cartCount: number;
  invoiceCount: number;
  unseenHistoryCount: number;
  invoicesRevision: string | number;
  readyToPickupCount: number;
  heartbeatUrl: string;
  pendingReviewProducts: Array<{ product_id: number; name: string }>;
};

export type InvoiceDetailItem = {
  productId: number;
  name: string;
  quantity: number;
  unitPriceFormatted: string;
  totalFormatted: string;
};

export type InvoiceDetailPageProps = {
  invoiceCount: number;
  backUrl: string;
  cartCount: number;
  documentTitle: string;
  invoiceNumber: string | null;
  orderMeta: {
    saleId: number;
    saleDateLabel: string;
    statusLabel: string;
    statusPillClass: string;
    statusIconClass: string;
    cancellationReason?: string | null;
    paymentDisplay: string;
    sourceDisplay: string;
  };
  totals: {
    subtotalFormatted: string;
    ivaFormatted: string;
    discountFormatted: string;
    totalFormatted: string;
    itemsCount: number;
  };
  items: InvoiceDetailItem[];
  printUrl: string;
};

