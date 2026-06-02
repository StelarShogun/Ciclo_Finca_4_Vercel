import type { PaginationLink } from '@/types/invoices';

export type NotificationRow = {
  id: string;
  createdAtLabel: string;
  message: string;
  actionUrl?: string | null;
  actionLabel?: string | null;
};

export type NotificationsPageProps = {
  notifications: NotificationRow[];
  links: PaginationLink[];
  cartCount: number;
};

