import { Suspense } from "react";

import { StoreHeader } from "@/components/storefront/store-header";
import { StoreFooter } from "@/components/storefront/store-footer";
import { FavoritesDrawerProvider } from "@/components/storefront/favorites-drawer";

export default function PublicLayout({ children }: { children: React.ReactNode }) {
  return (
    <FavoritesDrawerProvider>
      <div className="flex min-h-svh flex-col">
        <Suspense fallback={<div className="h-16 bg-brand-darkest" />}>
          <StoreHeader />
        </Suspense>
        <main className="flex-1">{children}</main>
        <StoreFooter />
      </div>
    </FavoritesDrawerProvider>
  );
}
