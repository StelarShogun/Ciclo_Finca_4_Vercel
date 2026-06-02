import type { ClientListPagination } from '@/types/pagination';

export type NotificationRow = {
  id: string;
  createdAtLabel: string;
  message: string;
  actionUrl?: string | null;
  actionLabel?: string | null;
};

export type NotificationsPageProps = {
  notifications: NotificationRow[];
  pagination: ClientListPagination;
  cartCount: number;
};

