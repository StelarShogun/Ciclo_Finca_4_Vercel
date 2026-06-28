import Link from "next/link";

export function StoreFooter() {
  return (
    <footer className="mt-16 border-t bg-[#051F20] text-[#DAF1DE]">
      <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm sm:flex-row sm:items-center sm:justify-between">
        <p className="font-semibold">Ciclo Finca 4</p>
        <nav className="flex gap-4 text-[#DAF1DE]/80">
          <Link href="/catalog" className="hover:text-white">Catálogo</Link>
          <Link href="/cart" className="hover:text-white">Carrito</Link>
          <Link href="/account" className="hover:text-white">Mi cuenta</Link>
        </nav>
        <p className="text-[#DAF1DE]/60">© {new Date().getFullYear()} Ciclo Finca</p>
      </div>
    </footer>
  );
}
