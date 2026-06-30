import {
  LayoutDashboard,
  Package,
  ShoppingCart,
  Truck,
  ClipboardList,
  Boxes,
  Users,
  FileText,
  Tags,
  Layers,
  ListChecks,
  PackageCheck,
  ScrollText,
  type LucideIcon,
} from "lucide-react";

export type NavItem = { title: string; href: string; icon: LucideIcon };

/** Navegación del panel admin. Las rutas se irán implementando por bloque. */
export const ADMIN_NAV: NavItem[] = [
  { title: "Dashboard", href: "/admin", icon: LayoutDashboard },
  { title: "Productos", href: "/admin/products", icon: Package },
  { title: "Marcas", href: "/admin/brands", icon: Tags },
  { title: "Categorías", href: "/admin/categories", icon: Layers },
  { title: "Opciones por tipo", href: "/admin/classification-catalog", icon: ListChecks },
  { title: "Ventas", href: "/admin/sales", icon: ShoppingCart },
  { title: "Encargos", href: "/admin/orders", icon: PackageCheck },
  { title: "Pedidos a proveedores", href: "/admin/supplier-orders", icon: ClipboardList },
  { title: "Proveedores", href: "/admin/suppliers", icon: Truck },
  { title: "Inventario", href: "/admin/inventory", icon: Boxes },
  { title: "Clientes", href: "/admin/clients", icon: Users },
  { title: "Reportes", href: "/admin/reports", icon: FileText },
  { title: "Auditoría", href: "/admin/audit", icon: ScrollText },
];
