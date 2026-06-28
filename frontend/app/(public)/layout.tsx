import { Suspense } from "react";

import { StoreHeader } from "@/components/storefront/store-header";
import { StoreFooter } from "@/components/storefront/store-footer";

export default function PublicLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-svh flex-col">
      <Suspense fallback={<div className="h-16 bg-[#051F20]" />}>
        <StoreHeader />
      </Suspense>
      <main className="flex-1">{children}</main>
      <StoreFooter />
    </div>
  );
}
